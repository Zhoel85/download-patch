(function (window, document, $) {
  'use strict';

  function translateBootstrapAttributes(root) {
    var scope = root || document;

    scope.querySelectorAll('[data-toggle]').forEach(function (el) {
      if (!el.hasAttribute('data-bs-toggle')) {
        el.setAttribute('data-bs-toggle', el.getAttribute('data-toggle'));
      }
    });

    scope.querySelectorAll('[data-target]').forEach(function (el) {
      if (!el.hasAttribute('data-bs-target')) {
        el.setAttribute('data-bs-target', el.getAttribute('data-target'));
      }
    });

    scope.querySelectorAll('[data-dismiss]').forEach(function (el) {
      if (!el.hasAttribute('data-bs-dismiss')) {
        el.setAttribute('data-bs-dismiss', el.getAttribute('data-dismiss'));
      }
    });

    scope.querySelectorAll('[data-parent]').forEach(function (el) {
      if (!el.hasAttribute('data-bs-parent')) {
        el.setAttribute('data-bs-parent', el.getAttribute('data-parent'));
      }
    });
  }

  function installJqueryBridges() {
    if (!$ || !window.bootstrap) return;

    if (!$.fn.modal) {
      $.fn.modal = function (action) {
        return this.each(function () {
          var instance = bootstrap.Modal.getOrCreateInstance(this);
          if (action === 'hide') instance.hide();
          else if (action === 'toggle') instance.toggle();
          else instance.show();
        });
      };
    }

    if (!$.fn.tab) {
      $.fn.tab = function (action) {
        return this.each(function () {
          var instance = bootstrap.Tab.getOrCreateInstance(this);
          if (!action || action === 'show') instance.show();
        });
      };
    }

    if (!$.fn.collapse) {
      $.fn.collapse = function (action) {
        return this.each(function () {
          var instance = bootstrap.Collapse.getOrCreateInstance(this, {toggle: false});
          if (action === 'hide') instance.hide();
          else if (action === 'toggle') instance.toggle();
          else instance.show();
        });
      };
    }

    if (!$.fn.dropdown) {
      $.fn.dropdown = function (action) {
        return this.each(function () {
          var instance = bootstrap.Dropdown.getOrCreateInstance(this);
          if (action === 'hide') instance.hide();
          else if (action === 'toggle') instance.toggle();
          else instance.show();
        });
      };
    }

    if (!$.fn.tooltip) {
      $.fn.tooltip = function () {
        return this.each(function () {
          bootstrap.Tooltip.getOrCreateInstance(this);
        });
      };
    }

    if (!$.fn.popover) {
      $.fn.popover = function () {
        return this.each(function () {
          bootstrap.Popover.getOrCreateInstance(this);
        });
      };
    }
  }

  function normalizeLegacyMarkup() {
    document.body.classList.remove('nav-md', 'nav-sm', 'campaign-ui-v2');

    document.querySelectorAll('.btn-default').forEach(function (el) {
      el.classList.add('btn-outline-secondary');
    });

    document.querySelectorAll('.panel-heading').forEach(function (el) {
      el.classList.add('card-header');
    });

    document.querySelectorAll('.panel-body').forEach(function (el) {
      el.classList.add('card-body');
    });
  }

  function boot() {
    translateBootstrapAttributes(document);
    installJqueryBridges();
    normalizeLegacyMarkup();

    if (window.jQuery && $.fn.select2) {
      $('.select2').each(function () {
        if (!$(this).data('select2')) {
          $(this).select2({width: '100%'});
        }
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})(window, document, window.jQuery);
