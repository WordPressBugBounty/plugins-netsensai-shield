<?php
// modal_popup.php
?>
<!-- Modal Popup Container -->
<div id="ns-shield-preload-modal" class="ns-shield-modal" style="display: none;">
  <div class="ns-shield-modal-overlay" style="
      position: fixed;
      top:0; left:0;
      width:100%; height:100%;
      background:rgba(0,0,0,0.5);
      z-index:1000;
  "></div>
  <div class="ns-shield-modal-content" style="
      position: fixed;
      top:50%; left:50%;
      transform: translate(-50%, -50%);
      background:#fff; padding:20px;
      border:1px solid #E6DB00;
      z-index:1001;
      max-width:500px; width:90%;
      color:#000;
  ">
    <!-- dynamiczne treści wstrzyknięte JS-em -->
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
  // 1) Tylko na stronie ustawień
  if (
    typeof nsShieldSettings !== 'object' ||
    !nsShieldSettings.pageSlug ||
    !new RegExp('[?&]page=' + nsShieldSettings.pageSlug + '(&|$)').test(window.location.search)
  ) return;

  if (typeof nsShieldModalConfig !== 'object') return;

  var cfg       = nsShieldModalConfig,
      checkbox  = document.getElementById('ns-shield-preload-checkbox'),
      modal     = document.getElementById('ns-shield-preload-modal'),
      container = modal.querySelector('.ns-shield-modal-content');

  if (!checkbox || !modal || !container) return;

  var keyEnable  = 'nsShieldModalShownEnable',
      keyDisable = 'nsShieldModalShownDisable';

  function show(html, type) {
    container.innerHTML = html;
    attachEvents(type);
    modal.style.display = 'block';
  }
  function hide() {
    modal.style.display = 'none';
  }
  function attachEvents(type) {
    // 2) Zamknięcie krzyżykiem
    var closeBtn = container.querySelector('#ns-shield-modal-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', function(){
        localStorage.setItem(type==='enable'?keyEnable:keyDisable, '1');
        hide();
      });
    }

    // 3) Obsługa przycisku OK (jeśli jest okBtn)
    var okBtn = container.querySelector('#ns-shield-modal-ok');
    if (okBtn) {
      okBtn.addEventListener('click', function(e){
        e.preventDefault();
        localStorage.setItem(keyEnable, '1');
        hide();
      });
    }

    // 4) Enable link/button
    var enBtn = container.querySelector('#ns-shield-submit-enable');
    if (enBtn) {
      enBtn.addEventListener('click', function(e){
        e.preventDefault();
        localStorage.setItem(keyEnable, '1');
        hide();
        window.open(cfg.enableUrl, '_blank');
      });
    }

    // 5) Disable link/button
    var disBtn = container.querySelector('#ns-shield-submit-disable');
    if (disBtn) {
      disBtn.addEventListener('click', function(e){
        e.preventDefault();
        localStorage.setItem(keyDisable, '1');
        hide();
        window.open(cfg.disableUrl, '_blank');
      });
    }

    // 6) Klik w overlay jako zamknięcie
    var overlay = modal.querySelector('.ns-shield-modal-overlay');
    if (overlay) {
      overlay.addEventListener('click', function(){
        localStorage.setItem(type==='enable'?keyEnable:keyDisable, '1');
        hide();
      });
    }
  }

  // 7) Wyświetl popup tylko raz dla każdej akcji
  if (checkbox.checked && !localStorage.getItem(keyEnable)) {
    show(cfg.enableContentHTML, 'enable');
  }
  else if (!checkbox.checked && cfg.wasEnabled === 'true' && !localStorage.getItem(keyDisable)) {
    show(cfg.disableContentHTML, 'disable');
  }

  // 8) Reset flag przy zmianie checkboxa
  checkbox.addEventListener('change', function(){
    localStorage.removeItem(keyEnable);
    localStorage.removeItem(keyDisable);
    if (this.checked) {
      show(cfg.enableContentHTML, 'enable');
    } else {
      show(cfg.disableContentHTML, 'disable');
    }
  });
});
</script>
