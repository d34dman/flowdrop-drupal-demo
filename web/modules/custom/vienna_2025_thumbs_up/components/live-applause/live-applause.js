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
      this.previousTotalClicks = 0;
      this.totalClicksInitialized = false;
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
      this.celebrationContainer = this.element.querySelector('.live-applause-widget--celebrations-container');
      this.thumbsButton = this.element.querySelector('.live-applause-widget__thumbs-button');
      this.connectionStatus = this.element.querySelector('.live-applause-widget__connection-status');
      this.myClicksElement = this.element.querySelector('#live-applause-my-clicks');
      this.totalClicksElement = this.element.querySelector('#live-applause-total-clicks');
      
      // Add body class to prevent scrollbars from floating animations (fallback for :has() selector)
      document.body.classList.add('live-applause-active');
      this.statsElement = this.element.querySelector('.live-applause-widget__stats');

      // Accessibility: Screen reader announcement elements
      this.announcementsElement = this.element.querySelector('#live-applause-announcements');
      this.celebrationsElement = this.element.querySelector('#live-applause-celebrations');
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
                // On first initialization, set previous total to current to avoid false celebrations
                if (!this.totalClicksInitialized) {
                  this.previousTotalClicks = parsedTotal;
                  this.totalClicksInitialized = true;
                }
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

      // Accessibility: Keyboard navigation support
      this.thumbsButton.addEventListener('keydown', (e) => {
        if (e.key === ' ' || e.key === 'Enter') {
          e.preventDefault();
          this.handleThumbsClick(e);
        }
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
      this.createThankYouMessage();

      // Update UI (local count only; total comes from server)
      this.updateMyClicks(this.myClicks);
      // Post thumbs up to server (no optimistic total update)
      this.postThumbsUp();

      // Accessibility: Update button aria attributes and announce click
      this.updateButtonAria();
      this.announceToScreenReader(`Thumbs up given. You have given ${this.myClicks} thumbs up.`);

      // Standard haptic feedback for personal clicks
      if (navigator.vibrate) {
        navigator.vibrate(50);
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

      // Use party emojis when community total is close to or just hit a milestone
      const isNearCommunityMilestone = this.isNearCommunityMilestone();
      const partyEmojis = ['🎉', '🥳', '✨', '🎊', '🌟', '💫'];
      const thumbsEmojis = ['👍', '👍🏻', '👍🏼', '👍🏽', '👍🏾', '👍🏿'];

      if (isNearCommunityMilestone) {
        thumb.classList.add('live-applause-widget__floating-thumb--party');
        thumb.textContent = partyEmojis[Math.floor(Math.random() * partyEmojis.length)];
      } else {
        thumb.textContent = thumbsEmojis[Math.floor(Math.random() * thumbsEmojis.length)];
      }

      // // Enhanced random positioning with wider spread
      // const buttonRect = this.celebrationContainer.getBoundingClientRect();
      // thumb.style.left = '0px';
      thumb.style.top = '50%';
      thumb.style.left = '50%';
      thumb.style.transform = 'translate(-50%, -50%)';
      this.celebrationContainer.appendChild(thumb);

      // Cleanup with appropriate timing based on animation duration
      const cleanupTime = isNearCommunityMilestone ? 2800 : 2500;
      setTimeout(() => {
        if (thumb.parentNode) {
          thumb.remove();
        }
      }, cleanupTime);
    }

    createThankYouMessage() {
      const thankYou = document.createElement('div');
      thankYou.classList.add('live-applause-widget__thank-you');
      thankYou.textContent = 'Thank You!';
      
      // Determine the milestone level based on myClicks
      let milestoneClass = 'live-applause-widget__thank-you--regular';
      let duration = 2000; // Default duration in milliseconds
      
      if (this.myClicks % 100 === 0) {
        // 100th click - EPIC!
        milestoneClass = 'live-applause-widget__thank-you--milestone-100';
        duration = 4000;
        // Add special text for 100th milestone
        thankYou.textContent = 'THANK YOU SO MUCH!';
      } else if (this.myClicks % 50 === 0) {
        // 50th click milestone
        milestoneClass = 'live-applause-widget__thank-you--milestone-50';
        duration = 3000;
        thankYou.textContent = 'Thank You!! 🎉';
      } else if (this.myClicks % 10 === 0) {
        // 10th click milestone
        milestoneClass = 'live-applause-widget__thank-you--milestone-10';
        duration = 2500;
        thankYou.textContent = 'Thank You! ✨';
      }
      
      thankYou.classList.add(milestoneClass);
      
      // Position relative to the button
      const buttonRect = this.thumbsButton.getBoundingClientRect();
      const containerRect = this.element.getBoundingClientRect();
      
      // Position relative to the widget container
      thankYou.style.left = (buttonRect.left - containerRect.left + buttonRect.width / 2) + 'px';
      thankYou.style.top = (buttonRect.top - containerRect.top + buttonRect.height / 2) + 'px';
      
      // Add to widget container
      this.element.appendChild(thankYou);
  
      
      // Screen reader announcement for milestones
      if (this.myClicks % 100 === 0) {
        this.announceToScreenReader(`Amazing! You've reached ${this.myClicks} thumbs up! Thank you so much for your enthusiasm!`);
      } else if (this.myClicks % 50 === 0) {
        this.announceToScreenReader(`Fantastic! ${this.myClicks} thumbs up! Thank you for your continued support!`);
      } else if (this.myClicks % 10 === 0) {
        this.announceToScreenReader(`Great! You've given ${this.myClicks} thumbs up! Thank you!`);
      }
      
      // Cleanup after animation completes
      setTimeout(() => {
        if (thankYou && thankYou.parentNode) {
          thankYou.remove();
        }
      }, duration);
    }

    isNearCommunityMilestone() {
      if (this.totalClicks < 10) return false;

      // Check if we're within 3 clicks of a decade milestone or just hit one
      const nextDecade = Math.ceil(this.totalClicks / 10) * 10;
      const distanceToNextDecade = nextDecade - this.totalClicks;
      const justHitDecade = this.totalClicks % 10 === 0;

      // Check if we're within 5 clicks of a century milestone or just hit one
      const nextCentury = Math.ceil(this.totalClicks / 100) * 100;
      const distanceToNextCentury = nextCentury - this.totalClicks;
      const justHitCentury = this.totalClicks % 100 === 0;

      // Show party emojis if:
      // 1. We just hit a milestone
      // 2. We're very close to a milestone (3 for decades, 5 for centuries)
      return justHitDecade || justHitCentury || distanceToNextDecade <= 3 || distanceToNextCentury <= 5;
    }

    createConfettiAnimation() {
      const interval = setInterval(() => {
        singleShotEmojiAnimation(['🎉', '👏', '👻', '👏', '👏']);
      }, 100);
      setTimeout(() => {
        clearInterval(interval);
      }, 500);
    }

    createFireworksAnimation() {
      const interval = setInterval(() => {
        singleShotEmojiAnimation(['🎆', '✨', '🌟', '💫', '🎇', '✨', '✨']);
      }, 100);
      setTimeout(() => {
        clearInterval(interval);
      }, 5000);
    }

    // Accessibility: Screen reader announcements
    announceToScreenReader(message, priority = 'polite') {
      const announcer = priority === 'assertive' ? this.celebrationsElement : this.announcementsElement;
      if (announcer) {
        announcer.textContent = message;
        // Clear after announcement to avoid repetition
        setTimeout(() => {
          announcer.textContent = '';
        }, 1000);
      }
    }

    // Accessibility: Update button aria attributes
    updateButtonAria() {
      if (this.thumbsButton) {
        const newLabel = `Give thumbs up (${this.myClicks} given, ${this.totalClicks} total)`;
        this.thumbsButton.setAttribute('aria-label', newLabel);
      }
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
      if (!this.connectionStatus) return;

      this.connectionStatus.className = `live-applause-widget__connection-status live-applause-widget__connection-status--${status}`;
      let label = '';
      switch (status) {
        case 'connected':
          label = '';
          break;
        case 'connecting':
          label = 'Connecting...';
          break;
        case 'disconnected':
          label = 'Disconnected';
          break;
      }

      // Update visual status with screen reader text
      this.connectionStatus.innerHTML =
        '<span class="sr-only">Connection status: </span>' +
        '<span class="live-applause-widget__connection-status-indicator" aria-hidden="true"></span> ' +
        label;

      // Accessibility: Announce connection status changes (only for significant changes)
      if (status === 'connected' || status === 'disconnected') {
        this.announceToScreenReader(`Connection status: ${label}`);
      }
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
      // Check for milestone celebrations before updating UI
      this.checkTotalMilestoneCelebrations(this.previousTotalClicks, count);

      // Update previous count for next comparison
      this.previousTotalClicks = count;

      // Animate the number display
      this.animateNumber(this.totalClicksElement, count);
    }

    checkTotalMilestoneCelebrations(previousTotal, newTotal) {
      // Don't trigger on first load
      if (previousTotal === 0) return;


      // Find all milestones between previous and new total
      const milestonesCrossed = this.findMilestonesCrossed(previousTotal, newTotal);

      milestonesCrossed.forEach(milestone => {
        if (milestone >= 100 && milestone % 100 === 0) {
          this.triggerCommunityFireworks(milestone);
        } else if (milestone >= 10 && milestone % 10 === 0) {
          this.triggerCommunityConfetti(milestone);
        }
      });
    }

    findMilestonesCrossed(previousTotal, newTotal) {
      const milestones = [];

      // Find decade milestones (10, 20, 30, etc.)
      const startDecade = Math.floor(previousTotal / 10) + 1;
      const endDecade = Math.floor(newTotal / 10);

      for (let decade = startDecade; decade <= endDecade; decade++) {
        const milestone = decade * 10;
        if (milestone > previousTotal && milestone <= newTotal) {
          milestones.push(milestone);
        }
      }

      return milestones;
    }

    triggerCommunityFireworks(milestoneCount) {
      this.createFireworksAnimation();

      // Accessibility: Announce celebration to screen readers
      this.announceToScreenReader(
        `Celebration! The community has reached ${milestoneCount} total thumbs up with spectacular fireworks!`,
        'assertive'
      );

    }

    triggerCommunityConfetti(milestoneCount) {
      this.createConfettiAnimation();

      // Accessibility: Announce celebration to screen readers
      this.announceToScreenReader(
        `Celebration! The community has reached ${milestoneCount} total thumbs up with confetti!`,
        'assertive'
      );

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
   * Helper function to create a single shot emoji animation that rises from the bottom of the screen.
   * 
   * @param {string[]} content - The content of the emoji to animate.
   * @param {number} duration - The duration of the animation in milliseconds.
   * @param {HTMLElement} element - The element to append the emoji to.
   */
  function singleShotEmojiAnimation(content = ['🎉', '👏', '👻', '✨'], duration = 4000, element = document.body) {
    const emoji = document.createElement('div');
    emoji.textContent = content[Math.floor(Math.random() * content.length)];
    emoji.style.position = 'absolute';
    emoji.style.left = Math.random() * window.innerWidth + 'px';
    emoji.style.top = (window.innerHeight - 50) + 'px';
    emoji.style.fontSize = '2rem';
    emoji.style.opacity = '0.3';
    emoji.style.pointerEvents = 'none';
    emoji.style.zIndex = '1';
    emoji.style.animation = 'live-applause-gentle-float 4s ease-out forwards';

    element.appendChild(emoji);

    setTimeout(() => {
      emoji.remove();
    }, duration);
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
        singleShotEmojiAnimation(['👀', '😴', '💤'], 4000);
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
