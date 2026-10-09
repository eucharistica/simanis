<?php declare(strict_types=1); ?>
</main>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="plugins/select2/js/select2.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>

<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
(function(){
  function adjustTables(){
    if (!window.jQuery || !jQuery.fn || !jQuery.fn.dataTable) return;
    try {
      $.fn.dataTable.tables({visible:true, api:true}).columns.adjust().responsive.recalc();
    } catch(e){}
  }

  function initIfExists(selector, title){
    if (!window.jQuery || !jQuery.fn || !jQuery.fn.DataTable) return;
    if (!document.querySelector(selector)) return;
    if ($.fn.dataTable.isDataTable(selector)) return;

    $(selector).DataTable({
      responsive: true,
      dom: 'lBfrtip',
      buttons: [
        { extend:'copyHtml5', text:'Copy' },
        { extend:'excelHtml5', text:'Excel', title: title }
      ],
      lengthMenu: [10, 25, 50, 100],
      pageLength: 10,
      ordering: true,
      autoWidth: false
    });
  }

  const saved = localStorage.getItem('vedika_sidebar_collapsed');
  if (saved === '1' && !window.matchMedia('(max-width: 767.98px)').matches) {
    $('#sidebar').addClass('collapsed');
  }

  $('#btnToggleSidebar').on('click', function(){
    $('#sidebar').toggleClass('collapsed');
    localStorage.setItem('vedika_sidebar_collapsed', $('#sidebar').hasClass('collapsed') ? '1' : '0');
    setTimeout(adjustTables, 260);
  });

  function openMobile(){
    $('#sidebar').removeClass('collapsed');
    localStorage.setItem('vedika_sidebar_collapsed', '0');
    $('#sidebar').addClass('mobile-open');
    $('#sidebar-backdrop').addClass('show');
  }

  function closeMobile(){
    $('#sidebar').removeClass('mobile-open');
    $('#sidebar-backdrop').removeClass('show');
  }

  window.openMobile = openMobile;
  window.closeMobile = closeMobile;

  $('#btnMobileMenu').on('click', openMobile);
  $('#btnCloseMobile').on('click', closeMobile);
  $('#sidebar-backdrop').on('click', closeMobile);

  $('#sidebar').on('click', 'a.sidebar-link', function(){
    if (window.matchMedia('(max-width: 767.98px)').matches) {
      closeMobile();
    }
  });

  $(function(){
    initIfExists('#dtMliteRajal', 'Mlite_Rajal');
    initIfExists('#dtMliteRanap', 'Mlite_Ranap');
    setTimeout(adjustTables, 50);
  });

  let tt;
  window.addEventListener('resize', function(){
    clearTimeout(tt);
    tt = setTimeout(function(){
      if (!window.matchMedia('(max-width: 767.98px)').matches) {
        $('#sidebar').removeClass('mobile-open');
        $('#sidebar-backdrop').removeClass('show');
      }
      adjustTables();
    }, 150);
  });
})();
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const searchInput = document.getElementById('sidebarMenuSearch');
  if (!searchInput) return;

  const menuItems = Array.from(document.querySelectorAll('.menu-item'));
  const sections = Array.from(document.querySelectorAll('.menu-section'));

  function normalizeText(txt) {
    return (txt || '')
      .toLowerCase()
      .replace(/\s+/g, ' ')
      .trim();
  }

  function filterSidebarMenu() {
    const keyword = normalizeText(searchInput.value);

    menuItems.forEach(item => {
      const textEl = item.querySelector('.menu-text');
      const text = normalizeText(textEl ? textEl.textContent : item.textContent);
      const match = keyword === '' || text.includes(keyword);
      item.style.display = match ? '' : 'none';
    });

    sections.forEach((section, index) => {
      const nextSection = sections[index + 1] || null;
      let sibling = section.nextElementSibling;
      let hasVisibleItem = false;

      while (sibling && sibling !== nextSection) {
        if (sibling.classList.contains('menu-item') && sibling.style.display !== 'none') {
          hasVisibleItem = true;
          break;
        }
        sibling = sibling.nextElementSibling;
      }

      section.style.display = hasVisibleItem ? '' : 'none';
    });
  }

  searchInput.addEventListener('input', filterSidebarMenu);
});
</script>
</body>
</html>
