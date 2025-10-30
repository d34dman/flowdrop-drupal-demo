(function() {
  "use strict";

  /**
   * Frontdesk App State Management
   */
  const FrontdeskApp = {
    currentStep: 1,
    maxStep: 8,
    stepsToSkip: [], // Steps that should be skipped based on CRM data
    userData: {
      aiPreference: null,
      drupalUsername: null,
      firstName: null,
      lastName: null,
      email: null,
      company: null,
      feedback: null,
      wantMore: null,
    },

    /**
     * Initialize the application
     */
    init() {
      this.bindEvents();
      this.updateProgress();
    },

    /**
     * Safely add event listener with null check
     */
    safeAddListener(elementId, event, handler) {
      const element = document.getElementById(elementId);
      if (element) {
        element.addEventListener(event, handler);
      }
    },

    /**
     * Bind all event listeners
     */
    bindEvents() {
      // Step 1: AI Preference
      document.querySelectorAll(".pill-button").forEach(button => {
        button.addEventListener("click", () => this.handleAiPreference(button));
      });

      // Step 2: Drupal Username
      this.safeAddListener("submit-username", "click", () => this.submitUsername());
      this.safeAddListener("skip-username", "click", () => this.skipUsername());
      this.safeAddListener("back-to-step-1", "click", () => this.goToStep(1));

      // Allow Enter key to submit
      this.safeAddListener("drupal-username", "keypress", (e) => {
        if (e.key === "Enter") this.submitUsername();
      });

      // Hide error hint when user starts typing again
      this.safeAddListener("drupal-username", "input", () => {
        this.hideUsernameError();
      });

      // Step 3: Confirm Info
      this.safeAddListener("confirm-info-btn", "click", () => {
        const nextStep = this.getNextStep(3);
        this.goToStep(nextStep);
      });
      this.safeAddListener("edit-info-btn", "click", () => this.showEditForm());

      // Step 3b: Edit Info
      this.safeAddListener("cancel-edit-btn", "click", () => this.cancelEdit());
      this.safeAddListener("save-info-btn", "click", () => this.saveEditedInfo());

      // Step 4: Company
      this.safeAddListener("submit-company", "click", () => this.submitCompany());
      this.safeAddListener("back-to-step-3", "click", () => {
        // When going back, respect the step order
        let prevStep = 3;
        while (this.stepsToSkip.includes(prevStep) && prevStep > 1) {
          prevStep--;
        }
        this.goToStep(prevStep);
      });

      this.safeAddListener("company-name", "keypress", (e) => {
        if (e.key === "Enter") this.submitCompany();
      });

      // Step 5: Feedback
      this.safeAddListener("submit-feedback", "click", () => this.submitFeedback());
      this.safeAddListener("back-to-step-4", "click", () => {
        // When going back, respect the step order
        let prevStep = 4;
        while (this.stepsToSkip.includes(prevStep) && prevStep > 1) {
          prevStep--;
        }
        this.goToStep(prevStep);
      });

      // Step 6: Want to know more?
      this.safeAddListener("want-more-yes", "click", () => this.submitFinal(true));
      this.safeAddListener("want-more-no", "click", () => this.submitFinal(false));

      // Step 7 & 8: Register Another
      this.safeAddListener("register-another-coffee", "click", () => this.resetForm());
      this.safeAddListener("register-another-success", "click", () => this.resetForm());
    },

    /**
     * Navigate to a specific step
     */
    goToStep(stepNumber) {
      const nextStep = document.getElementById(`step-${stepNumber}`);
      if (!nextStep) {
        console.warn(`Step ${stepNumber} not found`);
        return;
      }

      document.querySelectorAll(".step").forEach(step => {
        step.classList.remove("active");
      });

      nextStep.classList.add("active");
      this.currentStep = stepNumber;
      this.updateProgress();
      window.scrollTo({ top: 0, behavior: "smooth" });
    },

    /**
     * Update progress bar
     */
    updateProgress() {
      const progressFill = document.getElementById("progress-fill");
      if (progressFill) {
        const progress = (this.currentStep / this.maxStep) * 100;
        progressFill.style.width = progress + "%";
      }
    },

    /**
     * Show loading overlay
     */
    showLoading() {
      const overlay = document.getElementById("loading-overlay");
      if (overlay) {
        overlay.classList.add("active");
      }
    },

    /**
     * Hide loading overlay
     */
    hideLoading() {
      const overlay = document.getElementById("loading-overlay");
      if (overlay) {
        overlay.classList.remove("active");
      }
    },

    /**
     * Show error message
     */
    showError(message) {
      const errorEl = document.getElementById("error-message");
      errorEl.textContent = message;
      errorEl.classList.add("show");

      setTimeout(() => {
        errorEl.classList.remove("show");
      }, 5000);
    },

    /**
     * Show success message as conversation callout
     */
    showMessage(message, duration = null, iconType = 'sparkles') {
      // Create overlay if it doesn't exist
      let overlay = document.getElementById("message-overlay");
      if (!overlay) {
        overlay = document.createElement("div");
        overlay.id = "message-overlay";
        overlay.style.cssText = `
          position: fixed;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: rgba(9, 41, 99, 0.6);
          backdrop-filter: blur(4px);
          z-index: 10000;
          display: none;
          align-items: center;
          justify-content: center;
          animation: fadeIn 0.3s ease;
        `;
        document.body.appendChild(overlay);

        // Close overlay when clicking outside
        overlay.addEventListener("click", (e) => {
          if (e.target === overlay) {
            this.hideMessage();
          }
        });
      }

      // Create message callout if it doesn't exist
      let callout = document.getElementById("message-callout");
      if (!callout) {
        callout = document.createElement("div");
        callout.id = "message-callout";
        callout.style.cssText = `
          background: #ffffff;
          border-radius: 24px;
          box-shadow: 0 25px 80px rgba(9, 41, 99, 0.25), 0 0 1px rgba(9, 41, 99, 0.1);
          max-width: 700px;
          width: 92%;
          padding: 48px 56px;
          position: relative;
          animation: slideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
          border: 1px solid rgba(16, 185, 129, 0.1);
        `;

        // Create message content (no icon, just the message)
        const content = document.createElement("div");
        content.id = "message-content";
        content.style.cssText = `
          font-size: 28px;
          line-height: 1.5;
          color: #092963;
          white-space: pre-wrap;
          word-wrap: break-word;
          font-weight: 700;
          letter-spacing: -0.02em;
          text-align: center;
          margin-bottom: 32px;
        `;

        // Create continue button
        const continueBtn = document.createElement("button");
        continueBtn.textContent = "Continue";
        continueBtn.style.cssText = `
          background: linear-gradient(135deg, #3abcee 0%, #0891cf 100%);
          color: #ffffff;
          border: none;
          padding: 16px 40px;
          font-size: 17px;
          font-weight: 600;
          border-radius: 12px;
          cursor: pointer;
          transition: all 0.3s ease;
          box-shadow: 0 4px 12px rgba(58, 188, 238, 0.3);
          display: block;
          margin: 0 auto;
        `;
        continueBtn.onmouseover = () => {
          continueBtn.style.transform = "translateY(-2px)";
          continueBtn.style.boxShadow = "0 6px 20px rgba(58, 188, 238, 0.4)";
        };
        continueBtn.onmouseout = () => {
          continueBtn.style.transform = "translateY(0)";
          continueBtn.style.boxShadow = "0 4px 12px rgba(58, 188, 238, 0.3)";
        };
        continueBtn.onclick = () => this.hideMessage();

        callout.appendChild(content);
        callout.appendChild(continueBtn);
        overlay.appendChild(callout);
      }

      // Update content and show
      const content = document.getElementById("message-content");
      content.textContent = message;
      overlay.style.display = "flex";

      // Optional auto-dismiss
      if (duration && duration > 0) {
        setTimeout(() => {
          this.hideMessage();
        }, duration);
      }
    },

    /**
     * Hide message callout
     */
    hideMessage() {
      const overlay = document.getElementById("message-overlay");
      if (overlay) {
        overlay.style.display = "none";
      }
    },

    /**
     * Apply prefilled data from CRM
     */
    applyPrefilledData(prefilled) {
      if (!prefilled) return;

      if (prefilled.firstName) this.userData.firstName = prefilled.firstName;
      if (prefilled.lastName) this.userData.lastName = prefilled.lastName;
      if (prefilled.email) this.userData.email = prefilled.email;
      if (prefilled.company) this.userData.company = prefilled.company;
      if (prefilled.drupalUsername) this.userData.drupalUsername = prefilled.drupalUsername;

      console.log("Prefilled data applied:", prefilled);
    },

    /**
     * Determine the next step, skipping any that should be skipped
     */
    getNextStep(currentStep) {
      let nextStep = currentStep + 1;

      // Skip steps that are marked to be skipped
      while (this.stepsToSkip.includes(nextStep) && nextStep <= this.maxStep) {
        console.log(`Skipping step ${nextStep}`);
        nextStep++;
      }

      return nextStep;
    },

    /**
     * Handle API response (messages, prefilled data, skip steps)
     */
    handleApiResponse(result) {
      // Display success message if provided
      if (result.message) {
        this.showMessage(result.message);
      }

      // Apply prefilled data if provided
      if (result.prefilled) {
        this.applyPrefilledData(result.prefilled);
      }

      // Update steps to skip
      if (result.skipSteps && Array.isArray(result.skipSteps)) {
        this.stepsToSkip = [...new Set([...this.stepsToSkip, ...result.skipSteps])];
        console.log("Steps to skip:", this.stepsToSkip);
      }

      return result;
    },

    /**
     * Make API request
     */
    async apiRequest(endpoint, data = {}) {
      try {
        const response = await fetch(`/api/frontdesk/${endpoint}`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(data),
        });

        const result = await response.json();

        if (!response.ok) {
          throw new Error(result.error || "Request failed");
        }

        return result;
      } catch (error) {
        console.error("API Error:", error);
        throw error;
      }
    },

    /**
     * Handle AI preference selection
     */
    async handleAiPreference(button) {
      const preference = button.dataset.choice;
      this.userData.aiPreference = preference;

      this.showLoading();

      try {
        const result = await this.apiRequest("ai-preference", { preference });
        this.hideLoading();

        if (result.success) {
          this.handleApiResponse(result);
          const nextStep = this.getNextStep(1);
          this.goToStep(nextStep);
        }
      } catch (error) {
        this.hideLoading();
        this.showError("Failed to save preference. Please try again.");
      }
    },

    /**
     * Submit Drupal.org username
     */
    async submitUsername() {
      const username = document.getElementById("drupal-username").value.trim();

      // If no username provided, skip to the edit form (step 3b)
      if (!username) {
        this.skipUsername();
        return;
      }

      this.userData.drupalUsername = username;
      this.showLoading();

      try {
        const result = await this.apiRequest("lookup-user", { username });
        this.hideLoading();

        if (result.success && result.data) {
          // Handle response (messages, prefilled data, skip steps)
          this.handleApiResponse(result);

          // Populate user data
          this.userData.firstName = result.data.firstName;
          this.userData.lastName = result.data.lastName;
          this.userData.email = result.data.email;

          // Display the information
          document.getElementById("display-firstname").textContent = result.data.firstName || "Not found";
          document.getElementById("display-lastname").textContent = result.data.lastName || "Not found";
          document.getElementById("display-email").textContent = result.data.email || "Please provide";

          const nextStep = this.getNextStep(2);
          this.goToStep(nextStep);
        } else {
          // Username was provided but not found - show error with option to skip
          this.showUsernameError();
        }
      } catch (error) {
        this.hideLoading();
        // Network error or other issue - show error with option to skip
        this.showUsernameError();
      }
    },

    /**
     * Show username error hint with skip button
     */
    showUsernameError() {
      const errorHint = document.getElementById("username-error-hint");
      const skipButton = document.getElementById("skip-username");
      const usernameInput = document.getElementById("drupal-username");

      errorHint.style.display = "block";
      skipButton.style.display = "inline-block";
      usernameInput.focus();
    },

    /**
     * Hide username error hint and skip button
     */
    hideUsernameError() {
      const errorHint = document.getElementById("username-error-hint");
      const skipButton = document.getElementById("skip-username");

      errorHint.style.display = "none";
      skipButton.style.display = "none";
    },

    /**
     * Skip username lookup and go to edit form
     */
    skipUsername() {
      // Clear the username if user decides to skip
      this.userData.drupalUsername = null;

      // Hide error hint
      this.hideUsernameError();

      // Go directly to the edit form (step 3b) to collect information manually
      this.goToStep(3);
      // Immediately switch to edit form within step 3
      setTimeout(() => {
        this.showEditForm();
      }, 100);
    },

    /**
     * Show edit form
     */
    showEditForm() {
      // Pre-fill with existing data
      document.getElementById("edit-firstname").value = this.userData.firstName || "";
      document.getElementById("edit-lastname").value = this.userData.lastName || "";
      document.getElementById("edit-email").value = this.userData.email || "";

      document.getElementById("step-3").classList.remove("active");
      document.getElementById("step-3b").classList.add("active");
    },

    /**
     * Cancel editing
     */
    cancelEdit() {
      document.getElementById("step-3b").classList.remove("active");
      document.getElementById("step-3").classList.add("active");
    },

    /**
     * Save edited information
     */
    async saveEditedInfo() {
      const firstName = document.getElementById("edit-firstname").value.trim();
      const lastName = document.getElementById("edit-lastname").value.trim();
      const email = document.getElementById("edit-email").value.trim();

      if (!firstName || !lastName || !email) {
        this.showError("All fields are required");
        return;
      }

      // Basic email validation
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        this.showError("Please enter a valid email address");
        return;
      }

      this.showLoading();

      try {
        const result = await this.apiRequest("update-user", {
          firstName,
          lastName,
          email,
        });

        this.hideLoading();

        if (result.success) {
          // Handle response (messages, prefilled data, skip steps)
          this.handleApiResponse(result);

          this.userData.firstName = firstName;
          this.userData.lastName = lastName;
          this.userData.email = email;

          // Update display
          document.getElementById("display-firstname").textContent = firstName;
          document.getElementById("display-lastname").textContent = lastName;
          document.getElementById("display-email").textContent = email;

          const nextStep = this.getNextStep(3);
          this.goToStep(nextStep);
        }
      } catch (error) {
        this.hideLoading();
        this.showError("Failed to update information. Please try again.");
      }
    },

    /**
     * Submit company name (optional field)
     */
    async submitCompany() {
      const company = document.getElementById("company-name").value.trim();

      // Company is optional, so we proceed even if empty
      this.userData.company = company;
      this.showLoading();

      try {
        const result = await this.apiRequest("submit-company", { company });
        this.hideLoading();

        if (result.success) {
          this.handleApiResponse(result);
          const nextStep = this.getNextStep(4);
          this.goToStep(nextStep);
        }
      } catch (error) {
        this.hideLoading();
        this.showError("Failed to save company. Please try again.");
      }
    },

    /**
     * Submit feedback and go to next step
     */
    async submitFeedback() {
      const feedback = document.getElementById("feedback").value.trim();
      this.userData.feedback = feedback;

      this.showLoading();

      try {
        const result = await this.apiRequest("submit-feedback", { feedback });
        this.hideLoading();

        if (result.success) {
          this.handleApiResponse(result);
          this.goToStep(6);
        }
      } catch (error) {
        this.hideLoading();
        this.showError("Failed to submit feedback. Please try again.");
      }
    },

    /**
     * Submit final registration with "want more" preference
     */
    async submitFinal(wantMore) {
      this.userData.wantMore = wantMore;

      this.showLoading();

      try {
        const result = await this.apiRequest("submit-final", {
          wantMore,
          feedback: this.userData.feedback
        });
        this.hideLoading();

        if (result.success) {
          this.handleApiResponse(result);

          // Backend decides which screen to show
          // Step 7: Coffee/wait screen (if wantMore: true)
          // Step 8: Success screen (if wantMore: false)
          const targetStep = result.data?.targetStep || (wantMore ? 7 : 8);
          this.goToStep(targetStep);
        }
      } catch (error) {
        this.hideLoading();
        this.showError("Failed to submit registration. Please try again.");
      }
    },

    /**
     * Reset form for another registration
     */
    resetForm() {
      this.currentStep = 1;
      this.stepsToSkip = [];
      this.userData = {
        aiPreference: null,
        drupalUsername: null,
        firstName: null,
        lastName: null,
        email: null,
        company: null,
        feedback: null,
        wantMore: null,
      };

      // Clear form fields
      document.getElementById("drupal-username").value = "";
      document.getElementById("company-name").value = "";
      document.getElementById("feedback").value = "";
      document.getElementById("edit-firstname").value = "";
      document.getElementById("edit-lastname").value = "";
      document.getElementById("edit-email").value = "";

      // Hide error hint and skip button
      this.hideUsernameError();

      this.goToStep(1);
    },
  };

  // Initialize app when DOM is ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => FrontdeskApp.init());
  } else {
    FrontdeskApp.init();
  }
})();
