(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initMobileMenu();
    initFaqAccordion();
    initPlanCompare();
    initScrollHeader();
    initReveal();
    initCounters();
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

    links.addEventListener('click', function (event) {
      if (event.target.tagName === 'A') {
        links.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
      }
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

  function initPlanCompare() {
    var toggle = document.querySelector('[data-plan-compare-toggle]');
    var wrap = document.querySelector('[data-plan-compare]');
    var label = document.querySelector('[data-plan-compare-toggle-label]');

    if (!toggle || !wrap) {
      return;
    }

    toggle.addEventListener('click', function () {
      var isOpen = !wrap.hidden;

      wrap.hidden = isOpen;
      toggle.setAttribute('aria-expanded', String(!isOpen));

      if (label) {
        label.textContent = isOpen ? 'Comparar todos os recursos' : 'Ocultar comparativo';
      }

      if (!isOpen) {
        wrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    });
  }

  function initScrollHeader() {
    var nav = document.querySelector('.landing-navbar');

    if (!nav) {
      return;
    }

    function update() {
      nav.classList.toggle('scrolled', window.scrollY > 12);
    }

    window.addEventListener('scroll', update, { passive: true });
    update();
  }

  function initReveal() {
    var elements = document.querySelectorAll('.reveal');

    if (!elements.length) {
      return;
    }

    if (!('IntersectionObserver' in window)) {
      elements.forEach(function (el) {
        el.classList.add('is-visible');
      });
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });

    elements.forEach(function (el) {
      observer.observe(el);
    });
  }

  function initCounters() {
    var counters = document.querySelectorAll('[data-counter]');

    if (!counters.length) {
      return;
    }

    function animate(el) {
      var target = parseInt(el.getAttribute('data-counter'), 10) || 0;
      var duration = 1400;
      var start = null;

      function step(timestamp) {
        if (start === null) {
          start = timestamp;
        }

        var progress = Math.min((timestamp - start) / duration, 1);
        el.textContent = String(Math.floor(progress * target));

        if (progress < 1) {
          window.requestAnimationFrame(step);
        }
      }

      window.requestAnimationFrame(step);
    }

    if (!('IntersectionObserver' in window)) {
      counters.forEach(function (el) {
        el.textContent = el.getAttribute('data-counter');
      });
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          animate(entry.target);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.4 });

    counters.forEach(function (el) {
      observer.observe(el);
    });
  }
})();
