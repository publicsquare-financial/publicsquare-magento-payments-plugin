/*browser:true*/
/*global define*/
require([
  'jquery',
  'uiComponent',
  'Magento_Ui/js/modal/alert',
  'Magento_Ui/js/lib/view/utils/dom-observer',
  'mage/translate',
  'PublicSquare_Payments/js/publicsquare_payments'
], function ($, Class, alert, domObserver, $t, publicsquare) {
  'use strict';

  console.log('publicsquaree', publicsquare);

  return Class.extend({
    defaults: {
      $selector: null,
      selector: 'edit_form',
      container: 'publicsquare-elements-form',
      active: false,
      scriptLoaded: false,
      braintreeClient: null,
      braintreeHostedFields: null,
      hostedFieldsInstance: null,
      selectedCardType: null,
      selectorsMapper: {
        'expirationMonth': 'expirationMonth',
        'expirationYear': 'expirationYear',
        'number': 'cc_number',
        'cvv': 'cc_cid'
      },
      imports: {
        onActiveChange: 'active'
      }
    },

    initialize: function () {
      console.log('publicsquare', publicsquare);
    },

    /**
     * Set list of observable attributes
     * @returns {exports.initObservable}
     */
    initObservable: function () {
      var self = this;

      self.$selector = $('#' + self.selector);
      this._super()
        .observe([
          'active',
          'scriptLoaded',
          'selectedCardType'
        ]);

      // re-init payment method events
      self.$selector.off('changePaymentMethod.' + this.code)
        .on('changePaymentMethod.' + this.code, this.changePaymentMethod.bind(this));

      // listen block changes
      domObserver.get('#' + self.container, function () {
        if (self.scriptLoaded()) {
          self.$selector.off('submit');
          self.initPublicSquare();
        }
      });

      return this;
    },

    /**
     * Enable/disable current payment method
     * @param {Object} event
     * @param {String} method
     * @returns {exports.changePaymentMethod}
     */
    changePaymentMethod: function (event, method) {
      this.active(method === this.code);

      return this;
    },

    /**
     * Triggered when payment changed
     * @param {Boolean} isActive
     */
    onActiveChange: function (isActive) {
      if (!isActive) {
        this.$selector.off('submitOrder.publicsquare');

        return;
      }
      this.disableEventListeners();
      window.order.addExcludedPaymentMethod(this.code);

      if (!this.pk) {
        this.error($.mage.__('This payment is not available'));

        return;
      }

      this.enableEventListeners();

      if (!this.getLoaded()) {
        this.loadScript();
      }
    },

    /**
     * Load external Braintree SDK
     */
    loadScript: function () {
      var self = this,
        state = self.scriptLoaded;

      $('body').trigger('processStart');
      state(true);
      self.initPublicSquare();
      $('body').trigger('processStop');
    },

    /**
     * Retrieves client token and setup Braintree SDK
     */
    initPublicSquare: function () {
      var self = this;

      try {
        console.log(document.querySelectorAll(self.container));
        publicsquare.initElements({
          apiKey: self.pk,
          selector: `#${self.container}`
        }, () => { });
      } catch (e) {
        $('body').trigger('processStop');
        self.error(e.message);
      }
    },

    /**
     * Show alert message
     * @param {String} message
     */
    error: function (message) {
      alert({
        content: message
      });
    },

    /**
     * Enable form event listeners
     */
    enableEventListeners: function () {
      this.$selector.on('submitOrder.publicsquare', this.submitOrder.bind(this));
    },

    /**
     * Disable form event listeners
     */
    disableEventListeners: function () {
      this.$selector.off('submitOrder');
      this.$selector.off('submit');
    },

    /**
     * Store payment details
     * @param {String} nonce
     */
    setPaymentDetails: function (nonce) {
      this.$selector.find('[name="payment[payment_method_nonce]"]').val(nonce);
    },

    /**
     * Trigger order submit
     */
    submitOrder: async function () {
      var self = this;

      const firstName = $('#order-billing_address_firstname').val();
      const lastName = $('#order-billing_address_lastname').val();

      self.$selector.validate().form();
      self.$selector.trigger('afterValidate.beforeSubmit');

      // validate parent form
      if (self.$selector.validate().errorList.length) {
        $('body').trigger('processStop');

        return false;
      }

      if (!publicsquare.cardElement.metadata.valid) {
        $('body').trigger('processStop');
        self.error($t('Some card input fields are invalid.'));

        return false;
      } else if (!firstName || !lastName) {
        $('body').trigger('processStop');
        self.error($t('Please enter a valid cardholder name in the billing address section.'));
        return false;
      }

      try {
        // Tokenize the card in PublicSquare
        const card = await self.createCard(
          `${firstName} ${lastName}`
        );
        console.log(card);
        // Submit the payment
        self.setPaymentDetails(card.id);
        this.$selector.find('[type="submit"]').trigger('click');
      } catch (error) {
        console.log(error);
        $('body').trigger('processStop');
        self.error($t(error.responseJSON && error.responseJSON.message ? error.responseJSON.message : self.errorMessage));
      }
    },

    createCard: function (cardholder_name) {
      return publicsquare.createCard(cardholder_name, publicsquare.cardElement);
    },

    /**
     * Place order
     */
    placeOrder: function () {
      $('#' + this.selector).trigger('realOrder');
    },

    /**
     * Get jQuery selector
     * @param {String} field
     * @returns {String}
     */
    getSelector: function (field) {
      return '#' + this.code + '_' + field;
    },

    getLoaded() {
      return !!$(`#${this.container}`).children().length;
    }
  });
});