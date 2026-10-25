(function (window, document, $) {
  'use strict';

  var cfg = window.SAR_UI || {};

  function all(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function text(el) {
    return (el && el.textContent ? el.textContent : '').replace(/\s+/g, ' ').trim();
  }

  function translateBootstrapAttributes(root) {
    var scope = root || document;
    [
      ['data-toggle', 'data-bs-toggle'],
      ['data-target', 'data-bs-target'],
      ['data-dismiss', 'data-bs-dismiss'],
      ['data-parent', 'data-bs-parent']
    ].forEach(function (pair) {
      all('[' + pair[0] + ']', scope).forEach(function (el) {
        if (!el.hasAttribute(pair[1])) el.setAttribute(pair[1], el.getAttribute(pair[0]));
      });
    });
  }

  function installJqueryBridges() {
    if (!$ || !window.bootstrap) return;

    var map = {
      modal: bootstrap.Modal,
      tab: bootstrap.Tab,
      collapse: bootstrap.Collapse,
      dropdown: bootstrap.Dropdown,
      tooltip: bootstrap.Tooltip,
      popover: bootstrap.Popover
    };

    Object.keys(map).forEach(function (name) {
      if ($.fn[name]) return;

      $.fn[name] = function (action) {
        return this.each(function () {
          var Ctor = map[name];
          var instance = Ctor.getOrCreateInstance(this, name === 'collapse' ? {toggle:false} : undefined);

          if (action === 'hide' && instance.hide) instance.hide();
          else if (action === 'toggle' && instance.toggle) instance.toggle();
          else if ((!action || action === 'show') && instance.show) instance.show();
        });
      };
    });
  }

  function iconFor(label) {
    var s = String(label || '').toLowerCase();
    var map = [
      [/hapus|delete|remove/, 'bx-trash'],
      [/edit|ubah|perbarui/, 'bx-edit'],
      [/tambah|add|buat baru/, 'bx-plus'],
      [/simpan|save/, 'bx-save'],
      [/cetak|print/, 'bx-printer'],
      [/export|unduh|download/, 'bx-download'],
      [/import|upload/, 'bx-upload'],
      [/cari|search|tampilkan|filter/, 'bx-search'],
      [/detail|lihat|view|buka/, 'bx-show'],
      [/kembali|back/, 'bx-arrow-back'],
      [/refresh|reload|sinkron/, 'bx-refresh'],
      [/verifikasi|approve|setujui/, 'bx-check-circle'],
      [/finalisasi|final/, 'bx-lock-alt'],
      [/rollback|pulihkan/, 'bx-revision'],
      [/login|masuk/, 'bx-log-in']
    ];

    for (var i = 0; i < map.length; i++) {
      if (map[i][0].test(s)) return map[i][1];
    }

    return '';
  }

  function normalizeButtons(root) {
    all('.btn-default', root).forEach(function (el) {
      el.classList.add('btn-outline-secondary');
    });

    all('.btn-xs', root).forEach(function (el) {
      el.classList.add('btn-sm');
    });

    all('.btn', root).forEach(function (btn) {
      if (btn.querySelector('i.bx, i.fa, svg')) return;
      var icon = iconFor(text(btn));
      if (!icon) return;
      var i = document.createElement('i');
      i.className = 'bx ' + icon;
      btn.insertBefore(i, btn.firstChild);
    });

    all('table .btn', root).forEach(function (btn) {
      btn.classList.add('btn-sm');
    });
  }

  function normalizeCards(root) {
    all('.x_panel,.panel,.well', root).forEach(function (el) {
      el.classList.add('sar-card');
    });

    all('.panel-heading', root).forEach(function (el) {
      el.classList.add('card-header');
    });

    all('.panel-body', root).forEach(function (el) {
      el.classList.add('card-body');
    });

    all('form', root).forEach(function (form) {
      var controls = all('input:not([type="hidden"]),select,textarea', form).length;
      var buttons = all('.btn,button[type="submit"]', form).length;
      if (controls >= 2 && buttons >= 1 && !form.closest('.modal')) {
        form.classList.add('sar-filter-card');
      }
    });
  }

  function normalizeKpis(root) {
    all('.tile_count .tile_stats_count', root).forEach(function (box) {
      if (box.querySelector('.sar-kpi-icon')) return;

      var label = text(box.querySelector('.count_top')) || text(box);
      var i = document.createElement('span');
      i.className = 'sar-kpi-icon';
      var icon = document.createElement('i');
      icon.className = 'bx ' + (
        /tps|wilayah/.test(label.toLowerCase()) ? 'bx-map-pin' :
        /kandidat/.test(label.toLowerCase()) ? 'bx-user-pin' :
        /relawan|anggota|saksi/.test(label.toLowerCase()) ? 'bx-group' :
        /alert|insiden/.test(label.toLowerCase()) ? 'bx-error-circle' :
        /kegiatan/.test(label.toLowerCase()) ? 'bx-calendar-check' :
        /event/.test(label.toLowerCase()) ? 'bx-calendar-event' :
        'bx-bar-chart-alt-2'
      );
      i.appendChild(icon);
      box.insertBefore(i, box.firstChild);
    });
  }

  function normalizeTables(root) {
    all('table.table', root).forEach(function (table) {
      table.classList.add('table-hover', 'align-middle');

      if (!table.parentElement.classList.contains('table-responsive')) {
        var wrap = document.createElement('div');
        wrap.className = 'table-responsive';
        table.parentElement.insertBefore(wrap, table);
        wrap.appendChild(table);
      }

      var body = table.tBodies && table.tBodies[0];
      if (body && body.rows.length === 0) {
        var cols = table.tHead && table.tHead.rows[0] ? table.tHead.rows[0].cells.length : 1;
        var row = body.insertRow();
        row.className = 'sar-empty-row';
        var cell = row.insertCell();
        cell.colSpan = Math.max(1, cols);
        cell.innerHTML = '<div class="sar-empty-state"><i class="bx bx-inbox"></i><strong>Belum ada data</strong><span>Data akan tampil di sini setelah tersedia.</span></div>';
      }
    });
  }

  function normalizeBadges(root) {
    all('.label,.badge', root).forEach(function (el) {
      var s = text(el).toLowerCase();

      if (/aktif|selesai|lunas|verified|terverifikasi|siap|success|ok|go$|lulus/.test(s)) {
        el.classList.add('bg-label-success');
      } else if (/gagal|error|ditolak|no-go|bermasalah|merah/.test(s)) {
        el.classList.add('bg-label-danger');
      } else if (/pending|proses|menunggu|warning|conditional|kuning|perlu/.test(s)) {
        el.classList.add('bg-label-warning');
      } else if (/info|draft|setup|baru/.test(s)) {
        el.classList.add('bg-label-info');
      } else {
        el.classList.add('bg-label-secondary');
      }
    });
  }

  function normalizeModals(root) {
    all('.modal', root).forEach(function (modal) {
      var dialog = modal.querySelector('.modal-dialog');
      if (dialog && /modal-lg/.test(dialog.className)) dialog.classList.add('modal-dialog-scrollable');
    });
  }

  function initSelect2() {
    if (!window.jQuery || !$.fn.select2) return;

    $('.select2').each(function () {
      if ($(this).data('select2')) return;

      var modal = $(this).closest('.modal');
      var opts = {width:'100%'};
      if (modal.length) opts.dropdownParent = modal;

      $(this).select2(opts);
    });
  }

  function alertApi() {
    function fallback(message) {
      window.alert(message);
      return Promise.resolve({isConfirmed:true});
    }

    function fire(opts) {
      if (window.Swal && typeof Swal.fire === 'function') return Swal.fire(opts);
      return fallback(opts.text || opts.html || opts.title || '');
    }

    return {
      success: function (message, title) {
        return fire({icon:'success', title:title || 'Berhasil', text:String(message || ''), confirmButtonText:'OK'});
      },
      error: function (message, title) {
        return fire({icon:'error', title:title || 'Gagal', text:String(message || ''), confirmButtonText:'OK'});
      },
      warning: function (message, title) {
        return fire({icon:'warning', title:title || 'Perhatian', text:String(message || ''), confirmButtonText:'OK'});
      },
      info: function (message, title) {
        return fire({icon:'info', title:title || 'Informasi', text:String(message || ''), confirmButtonText:'OK'});
      },
      toast: function (message, icon) {
        return fire({
          toast:true, position:'top-end', icon:icon || 'success',
          title:String(message || ''), showConfirmButton:false, timer:2600, timerProgressBar:true
        });
      },
      confirm: function (message, options) {
        options = options || {};
        if (!window.Swal || typeof Swal.fire !== 'function') {
          return Promise.resolve({isConfirmed: window.confirm(message)});
        }
        return Swal.fire({
          icon: options.icon || 'warning',
          title: options.title || 'Konfirmasi',
          text: String(message || 'Lanjutkan tindakan ini?'),
          showCancelButton: true,
          confirmButtonText: options.confirmText || 'Ya, lanjutkan',
          cancelButtonText: options.cancelText || 'Batal',
          reverseButtons: true,
          focusCancel: true
        });
      }
    };
  }

  window.SARAlert = window.UMSRAlert = alertApi();

  function showFlash() {
    var list = Array.isArray(cfg.flash) ? cfg.flash : [];
    if (!list.length) return;

    var item = list[0];
    var message = String(item.message || '').trim();
    if (!message) return;

    all('.alert').forEach(function (el) {
      if (text(el).indexOf(message.slice(0, 80)) !== -1) el.style.display = 'none';
    });

    if (item.icon === 'error') SARAlert.error(message);
    else if (item.icon === 'warning') SARAlert.warning(message);
    else if (item.icon === 'info') SARAlert.info(message);
    else SARAlert.toast(message, 'success');
  }

  function dangerousLabel(el) {
    var label = text(el);
    var href = el.getAttribute ? String(el.getAttribute('href') || '') : '';
    var joined = (label + ' ' + href).toLowerCase();

    if (/hapus|delete|destroy|remove/.test(joined)) {
      return {title:'Hapus data?', message:'Data yang dihapus mungkin tidak dapat dikembalikan.', confirmText:'Ya, hapus', icon:'warning'};
    }
    if (/rollback|pulihkan/.test(joined)) {
      return {title:'Jalankan rollback?', message:'Pastikan backup tersedia sebelum melanjutkan.', confirmText:'Ya, rollback', icon:'warning'};
    }
    if (/finalisasi|finalize/.test(joined)) {
      return {title:'Finalisasi data?', message:'Pastikan data sudah benar sebelum difinalisasi.', confirmText:'Ya, finalisasi', icon:'question'};
    }
    return null;
  }

  function installConfirmGuard() {
    document.addEventListener('click', function (event) {
      var link = event.target.closest && event.target.closest('a');
      if (!link || link.dataset.sarConfirmed === '1') return;

      var rule = dangerousLabel(link);
      if (!rule) return;

      var href = link.getAttribute('href');
      if (!href || href === '#' || href.indexOf('javascript:') === 0) return;

      event.preventDefault();
      event.stopImmediatePropagation();

      SARAlert.confirm(rule.message, rule).then(function (result) {
        if (!result || !result.isConfirmed) return;
        link.dataset.sarConfirmed = '1';
        window.location.href = href;
      });
    }, true);

    document.addEventListener('submit', function (event) {
      var form = event.target;
      if (!form || form.dataset.sarConfirmed === '1') return;

      var submitter = event.submitter || document.activeElement;
      var rule = dangerousLabel(submitter || form);
      if (!rule) return;

      event.preventDefault();
      event.stopImmediatePropagation();

      SARAlert.confirm(rule.message, rule).then(function (result) {
        if (!result || !result.isConfirmed) return;
        form.dataset.sarConfirmed = '1';
        if (form.requestSubmit) form.requestSubmit(submitter && submitter.form === form ? submitter : undefined);
        else form.submit();
      });
    }, true);
  }

  function normalizeLegacyMarkup() {
    document.body.classList.remove('nav-md','nav-sm','campaign-ui-v2');

    var root = document.getElementById('legacy-content-bridge') || document;
    translateBootstrapAttributes(root);
    normalizeCards(root);
    normalizeButtons(root);
    normalizeKpis(root);
    normalizeTables(root);
    normalizeBadges(root);
    normalizeModals(root);
    initSelect2();
  }

  function boot() {
    translateBootstrapAttributes(document);
    installJqueryBridges();
    normalizeLegacyMarkup();
    installConfirmGuard();
    showFlash();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})(window, document, window.jQuery);
