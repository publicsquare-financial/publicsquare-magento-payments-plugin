// Copyright © PublicSquare Financial, LLC
//
// @package    PublicSquare_Payments
// @version    4.0.8
define(
  [
    'publicsquarejs'
  ],
  function (publicsquarejs) {
    'use strict';

    // Warning: This file should be kept lightweight as it is loaded on nearly all pages.

    return (window.publicsquare = {

      // Properties
      version: "1.0.0",
      publicsquareJs: null,
      cardElement: null,
      loading: false,

      initElements: async function (params = {}, callback) {
        if (!this.publicsquareJs && !this.loading) {
          this.loading = true;
          const _publicsquare = await publicsquarejs.init(params.apiKey)
          this.publicsquareJs = _publicsquare
          if (this.cardElement) {
            this.cardElement.unmount()
          }
          this.cardElement = _publicsquare.createCardElement({})
          this.cardElement.mount(params.selector)
          this.loading = false;
        } else if (!this.loading && this.cardElement) {
          this.cardElement.unmount()
          this.cardElement = this.publicsquareJs.createCardElement({})
          this.cardElement.mount(params.selector)
        }
        if (typeof callback === 'function') {
          callback(this)
        }
      },
      /**
       * Creates a card object in PublicSquare
       * @param {string} cardHolderName
       * @param {HTMLDivElement} card - This is the card element
       */
      createCard: async function (cardholder_name, card) {
        if (!this.publicsquareJs) {
          throw new Error('PublicSquare not initialized yet')
        } else {
          if (this.loading) {
            throw new Error('PublicSquare is still loading')
          }
          this.loading = true;
          const newCard = await this.publicsquareJs.cards.create({
            cardholder_name,
            card
          })
          this.loading = false;
          return newCard
        }
      }
    });
  }
);
