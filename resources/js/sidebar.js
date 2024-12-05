document.addEventListener('DOMContentLoaded', function() {
    const toggleButton = document.getElementById('toggle-btn');
    const sidebar = document.getElementById('sidebar');
  
    toggleButton.addEventListener('click', () => {
      sidebar.classList.toggle('close');
      toggleButton.classList.toggle('rotate');
      closeAllSubMenus();
    });
  
    sidebar.querySelectorAll('.dropdown-btn').forEach(button => {
      button.addEventListener('click', () => {
        if (!button.nextElementSibling.classList.contains('show')) {
          closeAllSubMenus();
        }
        button.nextElementSibling.classList.toggle('show');
        button.classList.toggle('rotate');
        
        if (sidebar.classList.contains('close')) {
          sidebar.classList.remove('close');
          toggleButton.classList.toggle('rotate');
        }
      });
    });
  
    function closeAllSubMenus() {
      sidebar.querySelectorAll('.show').forEach(ul => {
        ul.classList.remove('show');
        ul.previousElementSibling.classList.remove('rotate');
      });
    }
});
