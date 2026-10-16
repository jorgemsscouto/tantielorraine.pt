// tantielorraine.pt: comportamento mínimo do site (menu móvel e ano no rodapé).
// O site funciona sem JavaScript; isto apenas melhora a navegação em ecrãs pequenos.

(function () {
  var botao = document.querySelector(".menu-botao");
  var nav = document.getElementById("nav-principal");

  if (botao && nav) {
    botao.addEventListener("click", function () {
      var aberto = nav.classList.toggle("aberto");
      botao.setAttribute("aria-expanded", aberto ? "true" : "false");
    });
  }

  var ano = document.getElementById("ano");
  if (ano) {
    ano.textContent = new Date().getFullYear();
  }
})();
