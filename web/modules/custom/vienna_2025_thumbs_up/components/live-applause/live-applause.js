/**
 * @file
 * Live Applause Widget JavaScript
 */

(function (Drupal, drupalSettings) {
  'use strict';

  /**
   * Live Applause Widget Class
   */
  class LiveApplauseWidget {
    constructor(element, settings) {
      this.element = element;
      this.settings = settings;
      this.ws = null;
      this.myClicks = 0;
      this.totalClicks = 0;
      this.statsVisible = false;
      this.userId = this.generateUserId();
      this.lastClickTime = 0;
      this.clickCooldown = settings.clickCooldown || 100;
      this.isMobile = this.detectMobile();
      
      // Event configuration
      this.eventId = settings.eventId;
      this.apiBase = settings.apiBase;
      this.subscribeUrl = `${this.apiBase}/thumbs-up/${this.eventId}/subscribe?_format=json`;
      this.postThumbUrl = `${this.apiBase}/thumbs-up/${this.eventId}/up?_format=json`;
      this.subscriptionChannel = null;

      this.initializeElements();
      this.initializeMobileFeatures();
      this.connectWebSocket();
      this.setupEventListeners();
    }

    detectMobile() {
      return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
        (window.innerWidth <= 768) ||
        ('ontouchstart' in window);
    }

    initializeMobileFeatures() {
      if (this.isMobile) {
        // Prevent zoom on double tap
        document.addEventListener('touchstart', (e) => {
          if (e.touches.length > 1) {
            e.preventDefault();
          }
        }, { passive: false });

        // Prevent pull-to-refresh on mobile
        document.addEventListener('touchmove', (e) => {
          if (e.touches.length > 1) {
            e.preventDefault();
          }
        }, { passive: false });

        // Add mobile-specific classes
        this.element.classList.add('live-applause-widget--mobile');

        // Adjust initial layout for mobile
        this.adjustForOrientation();
      }
    }

    generateUserId() {
      return 'user_' + Math.random().toString(36).substr(2, 9);
    }

    initializeElements() {
      this.thumbsButton = this.element.querySelector('.live-applause-widget__thumbs-button');
      this.connectionStatus = this.element.querySelector('.live-applause-widget__connection-status');
      this.myClicksElement = this.element.querySelector('#live-applause-my-clicks');
      this.totalClicksElement = this.element.querySelector('#live-applause-total-clicks');
      this.statsElement = this.element.querySelector('.live-applause-widget__stats');
    }

    async connectWebSocket() {
      // Begin in connecting state
      this.updateConnectionStatus('connecting');
      try {
        // Fetch subscription info to get WS endpoint and channel
        const response = await fetch(this.subscribeUrl, {
          method: 'GET',
          headers: {
            'Accept': 'application/json'
          },
          credentials: 'include'
        });
        if (!response.ok) {
          throw new Error(`Subscribe request failed with status ${response.status}`);
        }
        const subInfo = await response.json();
        // Expecting: { endpoint: string, channel: string }
        if (!subInfo || typeof subInfo.endpoint !== 'string' || typeof subInfo.channel !== 'string') {
          throw new Error('Invalid subscription response structure');
        }
        this.subscriptionChannel = subInfo.channel;

        // Open WebSocket connection
        this.ws = new WebSocket(subInfo.endpoint);

        this.ws.onopen = () => {
          this.updateConnectionStatus('connected');
        };

        this.ws.onmessage = (event) => {
          try {
            const payload = JSON.parse(event.data);
            // Expected format:
            // { type: 'notification', data: { id, channel, message, timestamp } }
            if (payload && payload.type === 'notification' && payload.data && payload.data.channel === this.subscriptionChannel) {
              const messageVal = payload.data.message;
              const parsedTotal = typeof messageVal === 'string' ? parseInt(messageVal, 10) : Number(messageVal);
              if (!Number.isNaN(parsedTotal)) {
                this.totalClicks = parsedTotal;
                this.updateTotalClicks(this.totalClicks);
              }
            }
          } catch (err) {
            console.error('Failed to parse WS message:', err);
          }
        };

        this.ws.onerror = () => {
          // Error state; set to disconnected
          this.updateConnectionStatus('disconnected');
        };

        this.ws.onclose = () => {
          // Attempt reconnect after 3 seconds
          this.updateConnectionStatus('disconnected');
          setTimeout(() => {
            this.connectWebSocket();
          }, 3000);
        };
      } catch (error) {
        console.error('WebSocket setup failed:', error);
        this.handleConnectionError();
      }
    }

    setupEventListeners() {
      // Primary click handler
      this.thumbsButton.addEventListener('click', (e) => {
        this.handleThumbsClick(e);
      });

      // Enhanced touch events for mobile
      this.thumbsButton.addEventListener('touchstart', (e) => {
        e.preventDefault();
        this.handleThumbsClick(e);
      }, { passive: false });

      // Prevent double-tap zoom on mobile
      this.thumbsButton.addEventListener('touchend', (e) => {
        e.preventDefault();
      }, { passive: false });

      // Handle visibility change to reconnect if needed
      document.addEventListener('visibilitychange', () => {
        if (!document.hidden && this.ws && this.ws.readyState !== WebSocket.OPEN) {
          this.connectWebSocket();
        }
      });

      // Handle orientation change
      window.addEventListener('orientationchange', () => {
        // Small delay to allow orientation change to complete
        setTimeout(() => {
          this.adjustForOrientation();
        }, 100);
      });

      // Handle resize events for better mobile responsiveness
      window.addEventListener('resize', () => {
        this.adjustForOrientation();
      });
    }

    adjustForOrientation() {
      // Adjust button size based on viewport
      const viewport = {
        width: window.innerWidth,
        height: window.innerHeight
      };

      const isLandscape = viewport.width > viewport.height;
      const isSmallScreen = viewport.width < 480;
      const isVerySmallScreen = viewport.width < 360;
      const isLandscapeMobile = isLandscape && viewport.height < 500;

      // Reset to default first
      this.thumbsButton.style.width = '';
      this.thumbsButton.style.height = '';
      this.thumbsButton.style.fontSize = '';

      if (isLandscapeMobile) {
        this.thumbsButton.style.width = '80px';
        this.thumbsButton.style.height = '80px';
        this.thumbsButton.style.fontSize = '1.8rem';
      } else if (isVerySmallScreen) {
        this.thumbsButton.style.width = '100px';
        this.thumbsButton.style.height = '100px';
        this.thumbsButton.style.fontSize = '1.8rem';
      } else if (isSmallScreen) {
        this.thumbsButton.style.width = '120px';
        this.thumbsButton.style.height = '120px';
        this.thumbsButton.style.fontSize = '2.2rem';
      } else if (viewport.width < 768) {
        this.thumbsButton.style.width = '140px';
        this.thumbsButton.style.height = '140px';
        this.thumbsButton.style.fontSize = '2.5rem';
      }

      // Adjust connection status for small screens
      if (this.connectionStatus) {
        if (viewport.width < 360) {
          this.connectionStatus.style.maxWidth = '80px';
          this.connectionStatus.style.fontSize = '0.55rem';
        } else if (viewport.width < 480) {
          this.connectionStatus.style.maxWidth = '90px';
          this.connectionStatus.style.fontSize = '0.6rem';
        } else if (viewport.width < 768) {
          this.connectionStatus.style.maxWidth = '100px';
          this.connectionStatus.style.fontSize = '0.65rem';
        }
      }
    }

    handleThumbsClick(event) {
      const now = Date.now();

      // Rate limiting
      if (now - this.lastClickTime < this.clickCooldown) {
        return;
      }

      this.lastClickTime = now;
      this.myClicks++;

      // Show stats after first interaction
      if (!this.statsVisible) {
        this.showStats();
      }

      // Visual feedback
      this.animateButton();
      this.createRippleEffect(event);
      this.createFloatingThumb();

      // Update UI (local count only; total comes from server)
      this.updateMyClicks(this.myClicks);
      // Post thumbs up to server (no optimistic total update)
      this.postThumbsUp();

      // Enhanced haptic feedback on mobile
      if (navigator.vibrate) {
        // Different vibration patterns for different click counts
        if (this.myClicks % 10 === 0) {
          navigator.vibrate([100, 50, 100]); // Special pattern for milestones
        } else {
          navigator.vibrate(50); // Standard feedback
        }
      }

      // Add visual feedback for mobile
      this.addMobileTouchFeedback(event);
    }

    addMobileTouchFeedback(event) {
      // Add a temporary glow effect for mobile users
      const button = this.thumbsButton;
      button.style.boxShadow = '0 0 20px rgba(58, 188, 238, 0.8)';

      setTimeout(() => {
        button.style.boxShadow = '0 4px 20px var(--shadow-button)';
      }, 200);
    }

    animateButton() {
      this.thumbsButton.classList.add('live-applause-widget__thumbs-button--clicked');
      setTimeout(() => {
        this.thumbsButton.classList.remove('live-applause-widget__thumbs-button--clicked');
      }, 300);
    }

    createRippleEffect(event) {
      const button = this.thumbsButton;
      const rect = button.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = event.clientX - rect.left - size / 2;
      const y = event.clientY - rect.top - size / 2;

      const ripple = document.createElement('span');
      ripple.classList.add('live-applause-widget__ripple');
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = x + 'px';
      ripple.style.top = y + 'px';

      button.appendChild(ripple);

      setTimeout(() => {
        ripple.remove();
      }, 600);
    }

    createFloatingThumb() {
      const thumb = document.createElement('div');
      thumb.classList.add('live-applause-widget__floating-thumb');
      thumb.textContent = '👍';

      // Random position around the button
      const buttonRect = this.thumbsButton.getBoundingClientRect();
      const x = buttonRect.left + (Math.random() - 0.5) * 100;
      const y = buttonRect.top + (Math.random() - 0.5) * 50;

      thumb.style.left = x + 'px';
      thumb.style.top = y + 'px';

      document.body.appendChild(thumb);

      setTimeout(() => {
        thumb.remove();
      }, 2000);
    }

    async postThumbsUp() {
      try {
        const res = await fetch(this.postThumbUrl, {
          method: 'POST',
          headers: {
            'Accept': 'application/json'
          },
          credentials: 'include'
        });
        if (!res.ok) {
          throw new Error(`Thumbs up request failed with status ${res.status}`);
        }
        // Server will broadcast updated total via WS; nothing else to do here
      } catch (error) {
        console.error('Failed to post thumbs up:', error);
      }
    }

    updateConnectionStatus(status) {
      this.connectionStatus.className = `live-applause-widget__connection-status live-applause-widget__connection-status--${status}`;
      let label = '';
      switch (status) {
        case 'connected':
          label = 'Connected';
          break;
        case 'connecting':
          label = 'Connecting...';
          break;
        case 'disconnected':
          label = 'Disconnected';
          break;
      }
      this.connectionStatus.innerHTML = '<span class="live-applause-widget__connection-status-indicator"></span> ' + label;
    }

    updateMyClicks(count) {
      this.animateNumber(this.myClicksElement, count);
    }

    showStats() {
      if (!this.statsVisible && this.statsElement) {
        this.statsElement.classList.add('live-applause-widget__stats--visible');
        this.statsVisible = true;
      }
    }

    updateTotalClicks(count) {
      this.animateNumber(this.totalClicksElement, count);
    }

    animateNumber(element, newValue) {
      const currentValue = parseInt(element.textContent) || 0;
      const increment = newValue > currentValue ? 1 : -1;
      const steps = Math.abs(newValue - currentValue);

      if (steps === 0) return;

      let current = currentValue;
      const stepDuration = Math.min(50, 200 / steps);

      const timer = setInterval(() => {
        current += increment;
        element.textContent = current;

        if (current === newValue) {
          clearInterval(timer);
          // Add a little bounce effect
          element.style.transform = 'scale(1.2)';
          setTimeout(() => {
            element.style.transform = 'scale(1)';
          }, 150);
        }
      }, stepDuration);
    }

    handleConnectionError() {
      this.updateConnectionStatus('disconnected');

      // Try to reconnect after 3 seconds
      setTimeout(() => {
        this.updateConnectionStatus('connecting');
        this.connectWebSocket();
      }, 3000);
    }
  }

  /**
   * Background animation function
   */
  function createBackgroundAnimation(element, settings) {
    if (!settings.enableBackgroundAnimation) {
      return;
    }

    setInterval(() => {
      if (Math.random() > 0.8) {
        const emoji = document.createElement('div');
        emoji.textContent = ['🎉', '👏', '🙌', '✨'][Math.floor(Math.random() * 4)];
        emoji.style.position = 'absolute';
        emoji.style.left = Math.random() * window.innerWidth + 'px';
        emoji.style.top = window.innerHeight + 'px';
        emoji.style.fontSize = '2rem';
        emoji.style.opacity = '0.3';
        emoji.style.pointerEvents = 'none';
        emoji.style.zIndex = '1';
        emoji.style.animation = 'live-applause-float-up 4s linear forwards';

        document.body.appendChild(emoji);

        setTimeout(() => {
          emoji.remove();
        }, 4000);
      }
    }, 3000);
  }

  /**
   * Drupal behavior for Live Applause Widget
   */
  Drupal.behaviors.liveApplauseWidget = {
    attach: function (context, settings) {
      const widgets = context.querySelectorAll('.live-applause-widget[data-live-applause-widget]:not(.live-applause-widget--processed)');
      
      widgets.forEach((widget) => {
        widget.classList.add('live-applause-widget--processed');
        const widgetSettings = JSON.parse(widget.getAttribute('data-live-applause-widget'));
        
        // Initialize the widget
        const instance = new LiveApplauseWidget(widget, widgetSettings);
        
        // Store instance on element for potential future access
        widget.liveApplauseWidgetInstance = instance;
        
        // Start background animation if enabled
        setTimeout(() => {
          createBackgroundAnimation(widget, widgetSettings);
        }, 2000);
      });
    }
  };

})(Drupal, drupalSettings);
