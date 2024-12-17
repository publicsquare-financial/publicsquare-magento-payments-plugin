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
      version: "4.0.8",
      publicsquareJs: null,
      cardElement: null,

      initElements: async function (params = {}, callback) {
        if (!this.publicsquareJs) {
          const _publicsquare = await publicsquarejs.init(params.apiKey)
          this.publicsquareJs = _publicsquare
          this.cardElement = _publicsquare.createCardElement({})
          console.log('cardElement', this.cardElement)
          this.cardElement.mount(params.selector)

          if (typeof callback === 'function') {
            callback(this)
          }
        }
      },
      /**
       * Creates a card object in PublicSquare
       * @param {string} cardHolderName
       * @param {HTMLDivElement} card
       */
      createCard: async function (cardholder_name, card) {
        if (!this.publicsquareJs) {
          throw new Error('PublicSquare not initialized yet')
        } else {
          const newCard = await this.publicsquareJs.cards.create({
            cardholder_name,
            card
          })
          return newCard
        }
      }
    });
  }
);
