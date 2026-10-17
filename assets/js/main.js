// tantielorraine.pt: comportamento mínimo do site (menu móvel, ano no rodapé, envio do pedido de informação e
// medição de visitas com autorização). O site funciona sem JavaScript; sem ele, também não há medição.

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

  // Medição de visitas (Google Analytics 4) só depois de aceite no aviso de cookies: até lá não se carrega nada da
  // Google. A escolha fica no próprio browser e pode ser mudada em "Cookies", no rodapé.
  var GA = "G-PMGEGTHM1W";
  var lingua = (document.documentElement.lang || "pt").slice(0, 2);
  var TEXTOS = {
    pt: { aviso: 'Com a sua autorização, usamos cookies do Google Analytics para medir as visitas e melhorar o site. ' +
            'Pode mudar a escolha quando quiser em "Cookies", no rodapé. <a href="/privacidade.html#cookies">Saber mais</a>.',
          aceitar: "Aceitar", recusar: "Recusar", regiao: "Aviso de cookies" },
    en: { aviso: "With your permission, we use Google Analytics cookies to measure visits and improve the website. " +
            'You can change your choice at any time under "Cookies" in the footer. <a href="/en/privacy.html#cookies">Find out more</a>.',
          aceitar: "Accept", recusar: "Decline", regiao: "Cookie notice" },
    es: { aviso: "Con su autorización, usamos cookies de Google Analytics para medir las visitas y mejorar el sitio. " +
            'Puede cambiar su elección cuando quiera en "Cookies", en el pie de página. <a href="/es/privacidad.html#cookies">Más información</a>.',
          aceitar: "Aceptar", recusar: "Rechazar", regiao: "Aviso de cookies" }
  };
  var txt = TEXTOS[lingua] || TEXTOS.pt;
  var ESCOLHA = "tl-cookies";
  var ler = function () { try { return localStorage.getItem(ESCOLHA); } catch (e) { return null; } };
  var gravar = function (v) { try { localStorage.setItem(ESCOLHA, v); } catch (e) {} };

  function medir() {
    if (window.gtag) { return; }
    window.dataLayer = window.dataLayer || [];
    window.gtag = function () { window.dataLayer.push(arguments); };
    window.gtag("js", new Date());
    window.gtag("config", GA);
    var s = document.createElement("script");
    s.async = true;
    s.src = "https://www.googletagmanager.com/gtag/js?id=" + GA;
    document.head.appendChild(s);
  }

  function apagarCookies() {
    window["ga-disable-" + GA] = true;
    document.cookie.split(";").forEach(function (c) {
      var nome = c.split("=")[0].trim();
      if (nome.indexOf("_ga") === 0) {
        ["", "; domain=" + location.hostname, "; domain=." + location.hostname.replace(/^www\./, "")].forEach(function (d) {
          document.cookie = nome + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/" + d;
        });
      }
    });
  }

  var aviso = document.createElement("div");
  aviso.className = "aviso-cookies";
  aviso.setAttribute("role", "region");
  aviso.setAttribute("aria-label", txt.regiao);
  aviso.hidden = true;
  aviso.innerHTML = '<p class="tl-text-body">' + txt.aviso + '</p>' +
    '<div class="aviso-cookies__botoes"><button class="tl-btn tl-btn--compact" type="button" data-cookies="aceite">' + txt.aceitar + '</button>' +
    '<button class="tl-btn tl-btn--compact" type="button" data-cookies="recusado">' + txt.recusar + '</button></div>';
  document.body.appendChild(aviso);
  aviso.addEventListener("click", function (e) {
    var v = e.target.getAttribute && e.target.getAttribute("data-cookies");
    if (!v) { return; }
    gravar(v);
    aviso.hidden = true;
    if (v === "aceite") { medir(); } else { apagarCookies(); }
  });

  var links = document.querySelector(".tl-footer__links");
  if (links) {
    var pref = document.createElement("button");
    pref.type = "button";
    pref.className = "preferencias-cookies tl-text-nav";
    pref.textContent = "Cookies";
    pref.addEventListener("click", function () { aviso.hidden = false; });
    links.appendChild(pref);
  }

  if (ler() === "aceite") { medir(); } else if (ler() !== "recusado") { aviso.hidden = false; }

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
      enviar.textContent = enviar.getAttribute("data-enviando") || "A enviar...";
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
