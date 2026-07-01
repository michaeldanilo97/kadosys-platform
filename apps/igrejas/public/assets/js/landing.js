(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initMobileMenu();
    initFaqAccordion();
    initScrollHeader();
  });

  function initMobileMenu() {
    var toggle = document.querySelector('[data-nav-toggle]');
    var links = document.querySelector('[data-nav-links]');

    if (!toggle || !links) {
      return;
    }

    toggle.addEventListener('click', function () {
      var isOpen = links.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', String(isOpen));
    });
  }

  function initFaqAccordion() {
    var items = document.querySelectorAll('[data-faq-item]');

    items.forEach(function (item) {
      var question = item.querySelector('.faq-question');
      var answer = item.querySelector('.faq-answer');

      if (!question || !answer) {
        return;
      }

      question.addEventListener('click', function () {
        var isOpen = item.classList.contains('open');

        items.forEach(function (other) {
          other.classList.remove('open');
          var otherAnswer = other.querySelector('.faq-answer');
          if (otherAnswer) {
            otherAnswer.style.maxHeight = null;
          }
        });

        if (!isOpen) {
          item.classList.add('open');
          answer.style.maxHeight = answer.scrollHeight + 'px';
        }
      });
    });
  }

  function initScrollHeader() {
    var nav = document.querySelector('.landing-navbar');

    if (!nav) {
      return;
    }

    window.addEventListener('scroll', function () {
      if (window.scrollY > 12) {
        nav.style.boxShadow = '0 8px 24px -16px rgba(22, 33, 58, 0.4)';
      } else {
        nav.style.boxShadow = 'none';
      }
    });
  }
})();
