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
      if (form.closest('.modal')) return;

      var visibleControls = all(
        'input:not([type="hidden"]):not([type="file"]),select,textarea',
        form
      );
      var buttons = all('.btn,button[type="submit"],input[type="submit"]', form);
      var selects = all('select,.select2', form).length;
      var hasTextarea = all('textarea', form).length > 0;
      var hasFile = all('input[type="file"]', form).length > 0;
      var label = text(form).toLowerCase();
      var method = String(form.getAttribute('method') || 'get').toLowerCase();

      var looksLikeFilter =
        visibleControls.length >= 1 &&
        visibleControls.length <= 7 &&
        buttons.length >= 1 &&
        !hasTextarea &&
        !hasFile &&
        (
          method === 'get' ||
          selects > 0 ||
          /cari|filter|tampilkan|periode|status|event|kandidat|wilayah|tahun/.test(label)
        );

      if (!looksLikeFilter) return;

      form.classList.add('sar-filter-card');

      if (all(':scope > .row', form).length) {
        all(':scope > .row', form).forEach(function (row) {
          row.classList.add('g-3', 'align-items-end');
        });
      } else {
        form.classList.add('sar-filter-flow');
      }
    });
  }

  function isVisibleElement(el) {
    if (!el || el.nodeType !== 1) return false;
    var style = window.getComputedStyle ? window.getComputedStyle(el) : null;
    return !style || (style.display !== 'none' && style.visibility !== 'hidden');
  }

  function directColumns(row) {
    return Array.prototype.slice.call(row.children || []).filter(function (child) {
      return isVisibleElement(child) && /(^|\s)col-(xs|sm|md|lg|xl)-\d+/.test(String(child.className || ''));
    });
  }

  function normalizeGridLayout(root) {
    Array.prototype.slice.call(root.children || []).forEach(function (child) {
      if (!isVisibleElement(child)) return;

      if (/((^|\s)col-(xs|sm|md|lg|xl)-\d+|col-md-offset-\d+)/.test(String(child.className || ''))) {
        child.classList.add('sar-single-col-full');
      }

      if (!child.classList.contains('row')) return;

      child.classList.add('sar-layout-row');
      var cols = directColumns(child);

      if (cols.length === 1) {
        cols[0].classList.add('sar-single-col-full');
      }
    });

    all(':scope > .row', root).forEach(function (row) {
      row.classList.add('sar-layout-row');
      var cols = directColumns(row);
      if (cols.length === 1) cols[0].classList.add('sar-single-col-full');
    });
  }

  function normalizeActionRows(root) {
    all('.x_title,.card-header,.panel-heading,.modal-footer', root).forEach(function (parent) {
      var directButtons = Array.prototype.slice.call(parent.children || []).filter(function (child) {
        return child.matches && child.matches('.btn,button');
      });

      if (directButtons.length >= 2) parent.classList.add('sar-action-row');
    });

    all('.pull-right', root).forEach(function (parent) {
      if (all('.btn,button', parent).length >= 2) parent.classList.add('sar-action-row');
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

  function findHeadingByText(root, needle) {
    needle = String(needle || '').toLowerCase();
    var nodes = all('h1,h2,h3,h4,h5,h6,.x_title,.panel-title,.card-header,strong', root);

    for (var i = 0; i < nodes.length; i++) {
      if (text(nodes[i]).toLowerCase().indexOf(needle) !== -1) return nodes[i];
    }

    return null;
  }

  function commonAncestors(root, elements) {
    if (!elements.length) return [];

    var list = [];
    var node = elements[0];

    while (node && node !== document) {
      if (
        node.nodeType === 1 &&
        elements.every(function (el) { return node.contains(el); })
      ) {
        list.push(node);
      }

      if (node === root) break;
      node = node.parentElement;
    }

    return list;
  }

  function normalizeCommandCenterWidth(root) {
    var coverage = findHeadingByText(root, 'coverage organisasi');
    var tasks = findHeadingByText(root, 'tugas / kegiatan tertunda');
    var filter = root.querySelector('form.sar-filter-card');

    if (!coverage || !tasks || !filter) return;

    root.classList.add('sar-command-center-page');

    /*
     * 031 corrective:
     * Jangan lagi menebak tipe wrapper lewat regex class. 030 gagal karena
     * regex literal terbit dengan escape ganda (\\s/\\d), sehingga
     * wrapper col-md-* tidak pernah dikenali.
     *
     * commonAncestors() sendiri sudah deterministik: node yang dikembalikan
     * adalah ancestor yang benar-benar MEMBUNGKUS filter + Coverage + Tugas.
     * Karena itu semua common ancestor antara ketiga area tersebut dan root
     * aman dibuat full-width pada halaman Command Center saja.
     */
    commonAncestors(root, [coverage, tasks, filter]).forEach(function (node) {
      if (node === root) return;
      node.classList.add('sar-command-common-full');
    });

    var coverageCard = coverage.closest('.card,.sar-card,.x_panel,.panel,.well');
    var taskCard = tasks.closest('.card,.sar-card,.x_panel,.panel,.well');

    if (coverageCard && taskCard) {
      var coverageParent = coverageCard.parentElement;
      var taskParent = taskCard.parentElement;

      if (
        coverageParent &&
        taskParent &&
        coverageParent.parentElement &&
        coverageParent.parentElement === taskParent.parentElement
      ) {
        coverageParent.parentElement.classList.add('sar-command-insight-row');
      }
    }
  }

  function visibleElementChildren(parent) {
    return Array.prototype.slice.call(parent && parent.children ? parent.children : []).filter(function (child) {
      if (!isVisibleElement(child)) return false;
      var tag = String(child.tagName || '').toLowerCase();
      return tag !== 'script' && tag !== 'style' && tag !== 'link';
    });
  }

  function isLegacyColumn(el) {
    return !!(
      el &&
      /(^|\s)col-(xs|sm|md|lg|xl)-\d+/.test(String(el.className || ''))
    );
  }

  function normalizeElectionCenterWidth(root) {
    var uri = String((cfg && cfg.currentUri) || '')
      .toLowerCase()
      .replace(/^\/+|\/+$/g, '');

    if (uri.indexOf('admin/electioncenter') !== 0) return;

    root.classList.add('sar-election-center-page');

    var passes = 0;

    while (passes < 6) {
      passes++;

      visibleElementChildren(root).forEach(function (child) {
        if (isLegacyColumn(child)) child.classList.add('sar-election-full');
      });

      all('.row', root).forEach(function (row) {
        row.classList.add('sar-election-row');

        var cols = visibleElementChildren(row).filter(isLegacyColumn);
        if (cols.length === 1) cols[0].classList.add('sar-election-full');
      });

      all(
        '.card,.sar-card,.x_panel,.panel,.well,form,table,.table-responsive,.dataTables_wrapper,.tab-content',
        root
      ).forEach(function (leaf) {
        var node = leaf;

        while (node && node !== root) {
          var parent = node.parentElement;

          if (!parent || parent === root) {
            if (node && isLegacyColumn(node)) {
              node.classList.add('sar-election-full');
            }
            break;
          }

          var visible = visibleElementChildren(parent);
          if (visible.length !== 1 || visible[0] !== node) break;

          if (
            isLegacyColumn(node) ||
            node.classList.contains('row') ||
            node.classList.contains('container') ||
            node.classList.contains('container-fluid') ||
            node.classList.contains('x_content') ||
            node.hasAttribute('style')
          ) {
            node.classList.add('sar-election-full');
          }

          node = parent;
        }
      });
    }
  }

  function normalizeLabelValue(value) {
    return String(value || '')
      .toLowerCase()
      .replace(/\s+/g, ' ')
      .trim();
  }

  function electionModuleNav(root) {
    var matches = all('a,button', root).filter(function (el) {
      var label = normalizeLabelValue(text(el));
      return (
        label === 'event' ||
        label.indexOf('kandidat') !== -1 ||
        label.indexOf('wilayah') !== -1 ||
        label.indexOf('dapil') !== -1 ||
        label === 'dpt' ||
        label.indexOf('dpt / pemilih') !== -1 ||
        label.indexOf('pemilih') !== -1
      );
    });

    if (matches.length < 3) return null;

    var node = matches[0];
    while (node && node !== root) {
      var count = matches.filter(function (el) { return node.contains(el); }).length;
      if (count >= 3) return node;
      node = node.parentElement;
    }

    return null;
  }

  function visibleContentBlocks(root, navBlock) {
    return all(
      '.card,.sar-card,.x_panel,.panel,.well,form,.table-responsive,.dataTables_wrapper,.tab-content',
      root
    ).filter(function (el) {
      if (!isVisibleElement(el)) return false;
      if (navBlock && navBlock.contains(el)) return false;

      var rect = el.getBoundingClientRect ? el.getBoundingClientRect() : null;
      if (rect && (rect.width < 180 || rect.height < 40)) return false;

      return true;
    });
  }

  function normalizeElectionOuterShell(root) {
    var uri = String((cfg && cfg.currentUri) || '')
      .toLowerCase()
      .replace(/^\/+|\/+$/g, '');

    if (uri.indexOf('admin/electioncenter') !== 0) return;

    var navBlock = electionModuleNav(root);
    if (!navBlock) return;

    var blocks = visibleContentBlocks(root, navBlock);
    if (!blocks.length) return;

    var landmarks = [navBlock, blocks[0], blocks[blocks.length - 1]];

    commonAncestors(root, landmarks).forEach(function (node) {
      if (node === root) return;
      node.classList.add('sar-election-shell-full');
    });

    /*
     * Naikkan lagi dari navBlock bila parent hanya punya satu jalur konten.
     * Ini menangkap outer col-md-9/10 atau wrapper ber-style width yang berada
     * di atas seluruh halaman tetapi tidak terdeteksi oleh 032.
     */
    var node = navBlock;
    while (node && node !== root) {
      var parent = node.parentElement;
      if (!parent || parent === root) break;

      var visible = visibleElementChildren(parent);
      if (visible.length === 1 && visible[0] === node) {
        node.classList.add('sar-election-shell-full');
        node = parent;
        continue;
      }

      break;
    }
  }

  function normalizeLegacyMarkup() {
    document.body.classList.remove('nav-md','nav-sm','campaign-ui-v2');

    var root = document.getElementById('legacy-content-bridge') || document;
    translateBootstrapAttributes(root);
    normalizeGridLayout(root);
    normalizeCards(root);
    normalizeCommandCenterWidth(root);
    normalizeElectionCenterWidth(root);
    normalizeElectionOuterShell(root);
    normalizeButtons(root);
    normalizeActionRows(root);
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
