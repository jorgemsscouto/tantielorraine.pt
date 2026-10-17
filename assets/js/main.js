// tantielorraine.pt: comportamento mínimo do site (menu móvel, ano no rodapé, envio dos formulários, perguntas da
// degustação conforme as compotas provadas e medição de visitas com autorização). O site funciona sem JavaScript;
// sem ele, também não há medição.

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

  // Formulários (pedido de informação e opinião de degustação): envio sem sair da página. Sem JavaScript, o
  // formulário é enviado da forma normal e o servidor redireciona para a mensagem (data-enviado ou data-erro).
  Array.prototype.forEach.call(document.querySelectorAll("[data-formulario]"), function (form) {
    if (!window.fetch || !window.FormData) { return; }
    var inicio = Date.now();
    var enviado = document.getElementById(form.getAttribute("data-enviado"));
    var erro = document.getElementById(form.getAttribute("data-erro"));
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
  });

  // Degustação: só aparecem as perguntas das compotas que a pessoa marcou como provadas. As outras ficam
  // desativadas (não são validadas nem enviadas); a escolha da preferida e a comparação final só aparecem com
  // duas ou mais. Sem JavaScript, aparece tudo e o servidor só conta as compotas marcadas.
  var prova = document.querySelector("[data-degustacao]");
  if (prova) {
    var provou = prova.querySelectorAll("[data-provou]");
    var primeira = prova.querySelector("[data-obrigatorio]");
    var aviso = prova.querySelector("[data-sem-escolha]");

    var atualizar = function () {
      var escolhidas = {};
      var n = 0;
      Array.prototype.forEach.call(provou, function (c) { if (c.checked) { escolhidas[c.value] = true; n++; } });

      primeira.setCustomValidity(n ? "" : primeira.getAttribute("data-obrigatorio"));
      if (aviso) { aviso.hidden = n > 0; }

      Array.prototype.forEach.call(prova.querySelectorAll("[data-amostra]"), function (f) {
        var ativa = !!escolhidas[f.getAttribute("data-amostra")];
        f.hidden = !ativa;
        f.disabled = !ativa;
        Array.prototype.forEach.call(f.querySelectorAll("input[name$=_global]"), function (r) { r.required = ativa; });
      });
      Array.prototype.forEach.call(prova.querySelectorAll("[data-varias]"), function (f) {
        f.hidden = n < 2;
        f.disabled = n < 2;
      });
      Array.prototype.forEach.call(prova.querySelectorAll("[data-compota]"), function (o) {
        var ativa = !!escolhidas[o.getAttribute("data-compota")];
        var r = o.querySelector("input");
        o.hidden = !ativa;
        r.disabled = !ativa;
        if (!ativa) { r.checked = false; }
      });
    };

    Array.prototype.forEach.call(provou, function (c) { c.addEventListener("change", atualizar); });
    window.addEventListener("pageshow", atualizar);
    atualizar();

    // Local preenchido pelo endereço do código QR (por exemplo, /degustacao.html?local=Mercado%20do%20Bolh%C3%A3o); a
    // pessoa pode corrigi-lo. Quem responde em casa escreve-o.
    var local = /[?&](?:local|evento)=([^&#]*)/.exec(location.search);
    if (local && !prova.elements.local.value) {
      try { prova.elements.local.value = decodeURIComponent(local[1].replace(/\+/g, " ")).trim().slice(0, 80); } catch (e) {}
    }
  }

  // Ditado nos campos de texto com data-ditado: o reconhecimento de voz do próprio navegador (Web Speech API) escreve no
  // campo o que a pessoa diz, e o texto pode ser corrigido antes do envio. O site não recebe áudio, só o texto. Onde o
  // navegador não o suporta (Firefox, alguns navegadores dentro de aplicações), o botão não aparece.
  var Reconhecimento = window.SpeechRecognition || window.webkitSpeechRecognition;
  var ditaveis = document.querySelectorAll("textarea[data-ditado]");
  if (Reconhecimento && ditaveis.length) {
    var DITADO = {
      pt: { ditar: "Ditar", parar: "Parar", titulo: "Ditar a resposta com o microfone",
            ouvir: "A ouvir... Fale normalmente; a voz é transcrita pelo serviço de voz do seu navegador. Carregue em Parar quando terminar.",
            fim: "Texto transcrito. Pode corrigi-lo antes de enviar.",
            permissao: "Sem acesso ao microfone. Autorize-o nas definições do navegador ou escreva a resposta.",
            silencio: "Não ouvimos nada. Carregue em Ditar e tente de novo.",
            erro: "Não foi possível transcrever. Escreva a resposta." },
      en: { ditar: "Dictate", parar: "Stop", titulo: "Dictate your answer with the microphone",
            ouvir: "Listening... Speak normally; your voice is transcribed by your browser's speech service. Press Stop when you finish.",
            fim: "Text transcribed. You can correct it before sending.",
            permissao: "No access to the microphone. Allow it in your browser settings or type your answer.",
            silencio: "We didn't hear anything. Press Dictate and try again.",
            erro: "Transcription is not available. Please type your answer." },
      es: { ditar: "Dictar", parar: "Parar", titulo: "Dictar la respuesta con el micrófono",
            ouvir: "Escuchando... Hable con normalidad; su voz la transcribe el servicio de voz de su navegador. Pulse Parar cuando termine.",
            fim: "Texto transcrito. Puede corregirlo antes de enviarlo.",
            permissao: "Sin acceso al micrófono. Autorícelo en los ajustes del navegador o escriba la respuesta.",
            silencio: "No hemos oído nada. Pulse Dictar e inténtelo de nuevo.",
            erro: "No se ha podido transcribir. Escriba la respuesta." }
    };
    var dt = DITADO[lingua] || DITADO.pt;
    var VOZ = { pt: "pt-PT", en: "en-GB", es: "es-ES" };
    var ICONE = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" ' +
      'stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="3" width="6" height="11" rx="3"/>' +
      '<path d="M5 11a7 7 0 0 0 14 0M12 18v3M9 21h6"/></svg>';
    var ativo = null;

    // Junta os segmentos reconhecidos. Em alguns Android cada segmento repete o anterior por inteiro: nesse caso
    // substitui-o em vez de o somar.
    var juntar = function (partes) {
      var r = [];
      partes.forEach(function (t) {
        if (!t) { return; }
        if (r.length && t.toLowerCase().indexOf(r[r.length - 1].toLowerCase()) === 0) { r[r.length - 1] = t; } else { r.push(t); }
      });
      return r.join(" ");
    };

    Array.prototype.forEach.call(ditaveis, function (campo) {
      var botao = document.createElement("button");
      var estado = document.createElement("span");
      var caixa = document.createElement("div");
      botao.type = "button";
      botao.className = "ditado__botao tl-text-ui";
      botao.title = dt.titulo;
      botao.setAttribute("aria-controls", campo.id);
      estado.className = "ditado__estado tl-text-ui-sm";
      estado.setAttribute("aria-live", "polite");
      caixa.className = "ditado";
      caixa.appendChild(botao);
      caixa.appendChild(estado);
      campo.parentNode.insertBefore(caixa, campo);
      var marcar = function (a) {
        botao.innerHTML = ICONE + "<span>" + (a ? dt.parar : dt.ditar) + "</span>";
        botao.setAttribute("aria-pressed", a ? "true" : "false");
        botao.classList.toggle("ativo", a);
      };
      marcar(false);

      botao.addEventListener("click", function () {
        if (ativo && ativo.botao === botao) { ativo.rec.stop(); return; }
        if (ativo) { ativo.rec.abort(); }
        var rec = new Reconhecimento();
        var base = campo.value.replace(/\s+$/, "");
        var aviso = "";
        rec.lang = VOZ[lingua] || "pt-PT";
        rec.continuous = true;
        rec.interimResults = true;
        rec.onresult = function (e) {
          var partes = [];
          for (var i = 0; i < e.results.length; i++) { partes.push(e.results[i][0].transcript.trim()); }
          var texto = juntar(partes);
          // Maiúscula no início de uma frase
          if (!base || /[.!?]$/.test(base)) { texto = texto.charAt(0).toUpperCase() + texto.slice(1); }
          var valor = (base ? base + " " : "") + texto;
          campo.value = campo.maxLength > 0 ? valor.slice(0, campo.maxLength) : valor;
          campo.scrollTop = campo.scrollHeight;
        };
        rec.onerror = function (e) {
          aviso = e.error === "aborted" ? "" : e.error === "no-speech" ? dt.silencio :
            (e.error === "not-allowed" || e.error === "service-not-allowed" || e.error === "audio-capture") ? dt.permissao : dt.erro;
        };
        rec.onend = function () {
          if (ativo && ativo.rec === rec) { ativo = null; }
          marcar(false);
          estado.textContent = aviso || (campo.value.replace(/\s+$/, "") !== base ? dt.fim : "");
        };
        try { rec.start(); } catch (x) { estado.textContent = dt.erro; return; }
        ativo = { rec: rec, botao: botao };
        marcar(true);
        estado.textContent = dt.ouvir;
      });
    });

    // Ao enviar o formulário, termina o ditado em curso
    document.addEventListener("submit", function () { if (ativo) { ativo.rec.stop(); } }, true);
  }
})();
