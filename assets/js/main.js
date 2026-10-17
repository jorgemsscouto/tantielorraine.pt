// tantielorraine.pt: comportamento mínimo do site (menu móvel, ano no rodapé e envio do pedido de informação).
// O site funciona sem JavaScript; isto apenas melhora a navegação em ecrãs pequenos e o envio do formulário.

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

  // Pedido de informação: envio sem sair da página (sem JavaScript, o formulário é enviado da forma normal)
  var form = document.querySelector("[data-formulario-pedido]");
  if (form && window.fetch && window.FormData) {
    var inicio = Date.now();
    var enviado = document.getElementById("pedido-enviado");
    var erro = document.getElementById("pedido-erro");
    var enviar = form.querySelector("button[type=submit]");
    var rotulo = enviar.textContent;

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      form.elements.t.value = Math.round((Date.now() - inicio) / 1000);
      enviar.disabled = true;
      enviar.textContent = "A enviar...";
      erro.classList.remove("visivel");

      fetch(form.action, { method: "POST", body: new FormData(form), headers: { Accept: "application/json" } })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (!d.ok) { throw d; }
          form.hidden = true;
          enviado.classList.add("visivel");
          enviado.focus();
        })
        .catch(function () {
          erro.classList.add("visivel");
          erro.focus();
        })
        .then(function () {
          enviar.disabled = false;
          enviar.textContent = rotulo;
        });
    });
  }
})();
