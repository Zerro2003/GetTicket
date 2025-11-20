document.addEventListener("DOMContentLoaded", function () {
  const navbarToggler = document.getElementById("navbar-toggler");
  const navbarCollapse = document.getElementById("navbar-collapse");

  if (navbarToggler && navbarCollapse) {
    navbarToggler.addEventListener("click", function () {
      navbarCollapse.classList.toggle("active");
      navbarToggler.classList.toggle("active");
    });
  }
});
