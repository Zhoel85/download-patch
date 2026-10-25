(function(){
'use strict';
var cfg=window.CAMPAIGN_PORTAL_UI||{};
function q(s,c){return (c||document).querySelector(s)}
function qa(s,c){return Array.prototype.slice.call((c||document).querySelectorAll(s))}
function esc(v){return String(v==null?'':v).replace(/[&<>"']/g,function(ch){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]})}
function url(path){return String(cfg.base||'/').replace(/\/$/,'')+'/'+String(path||'').replace(/^\//,'')}
function currentPath(){return String(window.location.pathname||'').toLowerCase().replace(/\/+$/,'')}
function isActive(path){var p=currentPath(),u=new URL(url(path),window.location.origin).pathname.toLowerCase().replace(/\/+$/,'');return p===u||p.indexOf(u+'/')===0}
function link(path,label){return '<li class="'+(isActive(path)?'active':'')+'"><a href="'+url(path)+'">'+esc(label)+'</a></li>'}
function item(path,label,icon){return '<li class="'+(isActive(path)?'active':'')+'"><a href="'+url(path)+'"><i class="fa '+icon+'"></i> '+esc(label)+'</a></li>'}
function group(label,icon,children,paths){var open=(paths||[]).some(isActive);return '<li class="cp-nav-group '+(open?'active open':'')+'"><a href="#" class="cp-nav-toggle" aria-expanded="'+(open?'true':'false')+'"><i class="fa '+icon+'"></i> '+esc(label)+' <span class="fa fa-chevron-down"></span></a><ul class="nav child_menu">'+children+'</ul></li>'}
var routeMeta=[
 {n:'command',m:['admin/operations360/dashboard'],e:'COMMAND CENTER',t:'Command Center',s:'Ringkasan kondisi event aktif, coverage organisasi, kesiapan lapangan, alert, dan aktivitas terbaru.'},
 {n:'event',m:['admin/electioncenter/events'],e:'PEMILIHAN & DATA',t:'Event',s:'Kelola event pemilihan, periode kerja, status, wilayah, dan konteks data yang aktif.'},
 {n:'candidate',m:['admin/electioncenter/candidates'],e:'PEMILIHAN & DATA',t:'Kandidat & Paslon',s:'Kelola kandidat, pasangan calon, caleg, partai, nomor urut, dapil, foto, dan profil.'},
 {n:'region',m:['admin/electioncenter/wilayah'],e:'PEMILIHAN & DATA',t:'Wilayah & Dapil',s:'Atur kabupaten, dapil, kecamatan, desa/kelurahan, TPS, mapping, dan validasi relasi.'},
 {n:'dpt',m:['admin/electioncenter/dpt'],e:'PEMILIHAN & DATA',t:'DPT / Pemilih',s:'Referensi pemilih per event dengan versi, pencarian, validasi, duplicate detection, dan audit akses.'},
 {n:'org',m:['admin/operations360/organization'],e:'OPERASIONAL',t:'Organisasi',s:'Struktur Korkab sampai Relawan, assignment wilayah, masa tugas, referral, coverage, dan histori.'},
 {n:'member',m:['admin/operations360/members'],e:'OPERASIONAL',t:'Anggota & Relawan',s:'Lookup NIK, identitas tunggal, assignment, referral, verifikasi, histori, import, dan export.'},
 {n:'activity',m:['admin/operations360/activities'],e:'OPERASIONAL',t:'Kegiatan & Timeline',s:'Rencana, jadwal, pelaksanaan, laporan, dokumentasi, tindak lanjut, dan timeline wilayah.'},
 {n:'map',m:['admin/operations360/map'],e:'OPERASIONAL',t:'Peta Operasional',s:'Peta kesiapan struktur, relawan, kegiatan, saksi, TPS, dan status operasional per wilayah.'},
 {n:'witness',m:['admin/operations360/witnesses'],e:'HARI H',t:'Saksi 360',s:'Penempatan, pelatihan, mandat, kesiapan, check-in, insiden, input hasil, dan finalisasi.'},
 {n:'eday',m:['admin/electiondaycenter'],e:'HARI H',t:'Election Day Command Center',s:'Pantau TPS, check-in saksi, hasil masuk, verifikasi, masalah, insiden, dan progres kecamatan.'},
 {n:'incident',m:['admin/electiondaycenter/incidents'],e:'HARI H',t:'Incident Center',s:'Kelola kejadian lapangan dari status baru, diproses, sampai selesai dengan PIC dan bukti.'},
 {n:'realcount',m:['admin/electiondaycenter/real_count'],e:'HARI H',t:'Real Count Internal',s:'Verifikasi hasil TPS, revision history, bukti, dan rekap internal hasil sementara.'},
 {n:'live',m:['admin/electiondaycenter/activity'],e:'HARI H',t:'Live Activity',s:'Timeline aktivitas operasional, notifikasi, hasil terverifikasi, insiden, dan perubahan struktur.'},
 {n:'bigscreen',m:['admin/electiondaycenter/big_screen'],e:'HARI H',t:'Big Screen',s:'Tampilan read-only untuk TV, projector, videowall, dan War Room.'},
 {n:'report',m:['admin/electiondaycenter/reports'],e:'LAPORAN',t:'Laporan & Analytics',s:'Laporan organisasi, relawan, wilayah, kegiatan, saksi, Hari H, Real Count, dan aktivitas.'},
 {n:'roles',m:['admin/rolepermissioncenter'],e:'SISTEM',t:'User, Role & Permission',s:'Kelola role canonical, permission, assignment aktif, dan compatibility bridge legacy.'},
 {n:'import',m:['admin/integrateddatacenter'],e:'SISTEM',t:'Import / Export Center',s:'Template, upload, mapping, preview, validation, dry-run, apply, rollback, dan histori import.'},
 {n:'privacy',m:['admin/auditprivacycenter'],e:'SISTEM',t:'Audit & Privasi',s:'Audit login, CRUD, export, file, role, assignment, masking NIK, retention, dan security event.'},
 {n:'deploy',m:['admin/deploymentsafetycenter'],e:'SISTEM · ADVANCED',t:'Deployment Safety Center',s:'Preflight, backup, dry-run, apply, rollback, dan deployment log.'},
 {n:'normalize',m:['admin/databasenormalizationcenter'],e:'SISTEM · ADVANCED',t:'Database Normalization',s:'Audit dan compatibility bridge database legacy tanpa perubahan destruktif.'},
 {n:'legacy',m:['admin/legacycompatibility'],e:'SISTEM · ADVANCED',t:'Legacy / Compatibility',s:'Pantau dependency legacy, read/write activity, dan safe freeze sebelum cleanup.'},
 {n:'ready',m:['admin/productionreadiness'],e:'SISTEM · ADVANCED',t:'Production Readiness',s:'Gate final GO / CONDITIONAL / NO-GO sebelum aplikasi masuk produksi.'}
];
var tabs={
 election:{label:'Pemilihan & Data',items:[['admin/ElectionCenter/events','Event'],['admin/ElectionCenter/candidates','Kandidat & Paslon'],['admin/ElectionCenter/wilayah','Wilayah & Dapil'],['admin/ElectionCenter/dpt','DPT / Pemilih']]},
 ops:{label:'Operasional',items:[['admin/Operations360/dashboard','Command Center'],['admin/Operations360/organization','Organisasi'],['admin/Operations360/members','Anggota & Relawan'],['admin/Operations360/activities','Kegiatan & Timeline'],['admin/Operations360/map','Peta Operasional']]},
 day:{label:'Hari H',items:[['admin/Operations360/witnesses','Saksi 360'],['admin/ElectionDayCenter','Election Day'],['admin/ElectionDayCenter/incidents','Incident Center'],['admin/ElectionDayCenter/real_count','Real Count'],['admin/ElectionDayCenter/activity','Live Activity'],['admin/ElectionDayCenter/big_screen','Big Screen']]},
 system:{label:'Sistem',items:[['admin/RolePermissionCenter','Role & Permission'],['admin/IntegratedDataCenter','Import / Export'],['admin/AuditPrivacyCenter','Audit & Privasi'],['admin/DeploymentSafetyCenter','Deployment'],['admin/DatabaseNormalizationCenter','Database'],['admin/LegacyCompatibility','Legacy'],['admin/ProductionReadiness','Readiness']]}
};
function meta(){var p=currentPath();for(var i=0;i<routeMeta.length;i++){for(var j=0;j<routeMeta[i].m.length;j++){var s='/'+routeMeta[i].m[j].toLowerCase();if(p===s||p.indexOf(s+'/')===0)return routeMeta[i]}}return {n:'page',e:'PORTAL PEMENANGAN',t:document.title||'Dashboard',s:'Kelola data dan aktivitas sesuai role serta event aktif.'}}
function tabSet(m){if(['event','candidate','region','dpt'].indexOf(m.n)>=0)return tabs.election;if(['command','org','member','activity','map'].indexOf(m.n)>=0)return tabs.ops;if(['witness','eday','incident','realcount','live','bigscreen'].indexOf(m.n)>=0)return tabs.day;if(['roles','import','privacy','deploy','normalize','legacy','ready'].indexOf(m.n)>=0)return tabs.system;return null}
function buildSuperMenu(){
 var root=q('#sidebar-menu');if(!root||!cfg.isSuper)return;
 var menu=q('.nav.side-menu',root);if(!menu)return;
 var html='';
 html+=item('admin/Operations360/dashboard','Command Center','fa-dashboard');
 html+=group('Pemilihan & Data','fa-bullseye',
  link('admin/ElectionCenter/events','Event')+link('admin/ElectionCenter/candidates','Kandidat & Paslon')+link('admin/ElectionCenter/wilayah','Wilayah & Dapil')+link('admin/ElectionCenter/dpt','DPT / Pemilih'),
  ['admin/ElectionCenter']);
 html+=group('Operasional','fa-sitemap',
  link('admin/Operations360/organization','Organisasi')+link('admin/Operations360/members','Anggota & Relawan')+link('admin/Operations360/activities','Kegiatan & Timeline')+link('admin/Operations360/map','Peta Operasional'),
  ['admin/Operations360/organization','admin/Operations360/members','admin/Operations360/activities','admin/Operations360/map']);
 html+=group('Hari H','fa-flag-checkered',
  link('admin/Operations360/witnesses','Saksi 360')+link('admin/ElectionDayCenter','Election Day')+link('admin/ElectionDayCenter/incidents','Incident Center')+link('admin/ElectionDayCenter/real_count','Real Count')+link('admin/ElectionDayCenter/activity','Live Activity')+link('admin/ElectionDayCenter/big_screen','Big Screen'),
  ['admin/Operations360/witnesses','admin/ElectionDayCenter']);
 html+=item('admin/ElectionDayCenter/reports','Laporan & Analytics','fa-bar-chart');
 html+=group('Sistem','fa-cogs',
  link('admin/RolePermissionCenter','User, Role & Permission')+link('admin/IntegratedDataCenter','Import / Export')+link('admin/AuditPrivacyCenter','Audit & Privasi')+
  link('admin/DeploymentSafetyCenter','Deployment Safety Center')+link('admin/DatabaseNormalizationCenter','Database Normalization')+link('admin/LegacyCompatibility','Legacy / Compatibility')+link('admin/ProductionReadiness','Production Readiness'),
  ['admin/RolePermissionCenter','admin/IntegratedDataCenter','admin/AuditPrivacyCenter','admin/DeploymentSafetyCenter','admin/DatabaseNormalizationCenter','admin/LegacyCompatibility','admin/ProductionReadiness']);
 menu.innerHTML=html;
 bindSuperMenu(menu);
}
function bindSuperMenu(menu){
 qa('.cp-nav-toggle',menu).forEach(function(a){
  a.addEventListener('click',function(e){
   e.preventDefault();
   e.stopPropagation();
   var li=a.parentNode;
   var opening=!li.classList.contains('open');
   qa('.cp-nav-group',menu).forEach(function(other){
    if(other===li)return;
    other.classList.remove('open');
    var oa=q('.cp-nav-toggle',other);
    if(oa)oa.setAttribute('aria-expanded','false');
   });
   li.classList.toggle('open',opening);
   a.setAttribute('aria-expanded',opening?'true':'false');
  });
 });
}
function roleCard(){var root=q('#sidebar-menu');if(!root||q('.cp-role-card'))return;var card=document.createElement('div');card.className='cp-role-card';card.innerHTML='<div class="cp-role-label">Akses Login</div><div class="cp-role-name">'+esc(cfg.roleLabel||'Pengguna')+'</div><div class="cp-role-event">'+esc(cfg.eventName||'Belum ada event aktif')+'</div>';root.parentNode.insertBefore(card,root)}
function topbar(){var nav=q('.top_nav .nav_menu');if(!nav||q('.cp-topbar-brand',nav))return;var el=document.createElement('div');el.className='cp-topbar-brand';el.innerHTML='<div class="cp-topbar-title">PORTAL PEMENANGAN</div><div class="cp-topbar-sub">'+esc(cfg.eventYear?'Event '+cfg.eventYear:(cfg.eventName||'Multi Event'))+'</div>';nav.insertBefore(el,nav.firstChild)}
function hero(){var col=q('.right_col');if(!col||q('.cp-page-hero',col))return;var m=meta();var h=document.createElement('div');h.className='cp-page-hero';h.innerHTML='<div class="cp-hero-eyebrow">'+esc(m.e)+'</div><div class="cp-hero-title">'+esc(m.t)+'</div><p class="cp-hero-sub">'+esc(m.s)+'</p><div class="cp-hero-side"><span class="cp-hero-badge">'+esc(cfg.roleLabel||'Portal')+'</span><div class="cp-hero-event">'+esc(cfg.eventName||'Belum ada event aktif')+'</div></div>';col.insertBefore(h,col.firstChild);var ts=tabSet(m);if(ts){var tb=document.createElement('div');tb.className='cp-module-tabs';var html='<div class="cp-tab-label">'+esc(ts.label)+'</div>';ts.items.forEach(function(it){html+='<a class="'+(isActive(it[0])?'active':'')+'" href="'+url(it[0])+'">'+esc(it[1])+'</a>'});tb.innerHTML=html;h.parentNode.insertBefore(tb,h.nextSibling)}}
function cleanupLegacyHeading(){
 var col=q('.right_col');if(!col)return;
 var m=meta();
 qa('.page-title',col).forEach(function(el){el.classList.add('cp-legacy-page-title-hidden')});
 Array.prototype.slice.call(col.children).forEach(function(el){
  if(!/^H[1-3]$/.test(el.tagName||''))return;
  var txt=(el.textContent||'').trim().toLowerCase();
  var title=String(m.t||'').trim().toLowerCase();
  if(title&&txt.indexOf(title)>=0)el.classList.add('cp-legacy-page-title-hidden');
 });
}
function enhance(){
 qa('.x_panel,.panel,.well,.card').forEach(function(e){e.classList.add('cp-surface')});
 qa('table.table').forEach(function(t){if(!t.parentNode.classList.contains('table-responsive')){var w=document.createElement('div');w.className='table-responsive';t.parentNode.insertBefore(w,t);w.appendChild(t)}});
 qa('.label,.badge').forEach(function(e){var x=e.textContent.trim().toUpperCase();if(/LUNAS|LULUS|SELESAI|AKTIF|VERIFIED|READY|SIAP|GO$/.test(x))e.classList.add('label-success');else if(/GAGAL|DITOLAK|ERROR|BLOCK|NO-GO|MERAH|BELUM SIAP/.test(x))e.classList.add('label-danger');else if(/PROSES|PENDING|WAIT|PERLU|KUNING|CONDITIONAL/.test(x))e.classList.add('label-warning')});
 var filters=qa('form').filter(function(f){return qa('input,select',f).length>=2&&q('.btn',f)});filters.forEach(function(f){if(!f.classList.contains('cp-filter-surface'))f.classList.add('cp-filter-surface')});
}
function boot(){document.body.classList.add('campaign-ui-v2');buildSuperMenu();roleCard();topbar();hero();cleanupLegacyHeading();enhance()}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
})();