// Testes dos formulários (degustação e contactos) no navegador, contra o site local de testes/arrancar.sh.
// Verifica o comportamento dinâmico da degustação, o ditado (com um reconhecimento de voz simulado), o envio nas três
// línguas (com e sem JavaScript) e os emails guardados pelo SMTP falso. Termina com código 1 se alguma verificação falhar.
// Uso: cd testes && npm install && npm test   (CHROMIUM=<caminho> se o Chromium não estiver em /opt/pw-browsers)
const { chromium } = require('playwright');
const fs = require('fs');
const BASE = process.env.BASE || 'http://127.0.0.1:8095';
const CAIXA = (process.env.PASTA || '/tmp/tantie-teste') + '/caixa';
const espera = ms => new Promise(r => setTimeout(r, ms));
// Emails diferentes em cada execução, para não esbarrar no limite de envios por email
const ID = Date.now().toString(36);
const mail = nome => `${nome}.${ID}@example.com`;
const caixa = () => fs.readdirSync(CAIXA).sort();
let falhas = 0;
function verificar(condicao, descricao) {
  console.log((condicao ? 'ok    ' : 'FALHA ') + descricao);
  if (!condicao) falhas++;
}
// Emails novos desde "antes": [{envelope, assunto}]
async function novos(antes, n) {
  for (let i = 0; i < 75; i++) {
    const lista = caixa().filter(f => !antes.includes(f));
    if (lista.length >= n) {
      return lista.map(f => {
        const bruto = fs.readFileSync(CAIXA + '/' + f, 'utf8');
        // Cabeçalhos longos vêm em várias linhas e com acentos codificados (RFC 2047): juntar e descodificar
        const assunto = (bruto.replace(/\r?\n[ \t]+/g, ' ').match(/^Subject: (.*)$/m) || [])[1] || '';
        const descodificado = assunto.replace(/\?=\s+=\?/g, '?==?').replace(/=\?UTF-8\?B\?([^?]*)\?=/gi, (_, b) => Buffer.from(b, 'base64').toString('utf8'));
        return { envelope: bruto.split('\r\n')[0], assunto: descodificado };
      });
    }
    await espera(200);
  }
  return [];
}
const VOZ = `window.__recs = [];
class Falso { constructor() { window.__recs.push(this); } start() {} stop() { setTimeout(() => this.onend && this.onend(), 10); }
  abort() { setTimeout(() => this.onend && this.onend(), 10); } emit(l) { this.onresult({ results: l.map(t => [{ transcript: t }]) }); } }
window.SpeechRecognition = Falso;`;

(async () => {
  const b = await chromium.launch({ executablePath: process.env.CHROMIUM || '/opt/pw-browsers/chromium' });
  const erros = [];
  const ctx = await b.newContext({ viewport: { width: 390, height: 844 } });
  await ctx.addInitScript(VOZ);
  const nota = (p, n, v) => p.click(`label:has(input[name="${n}"][value="${v}"])`);

  // Degustação em português, com o local vindo do código QR
  const p = await ctx.newPage();
  p.on('pageerror', e => erros.push(e.message));
  await p.goto(BASE + '/degustacao.html?local=Mercado%20do%20Bolh%C3%A3o', { waitUntil: 'networkidle' });
  verificar(await p.inputValue('[name=local]') === 'Mercado do Bolhão', 'local preenchido pelo endereço (?local=)');
  verificar(await p.locator('[data-amostra]:visible').count() === 0, 'sem compotas marcadas, não aparece nenhuma classificação');
  await p.check('input[name="provou[]"][value=sbj]');
  verificar(await p.locator('[data-amostra]:visible').count() === 1 && !(await p.isVisible('[data-varias]')), 'uma compota: só a sua classificação, sem preferida nem comparação');
  await p.check('input[name="provou[]"][value=tcj]');
  verificar(await p.locator('[data-amostra]:visible').count() === 2 && await p.isVisible('[data-varias]'), 'duas compotas: preferida e comparação aparecem');
  verificar(await p.locator('.opcao:visible .opcao__desc').count() > 0, 'botões com o nome e os ingredientes (sem siglas sozinhas)');
  for (const [n, v] of [['sbj_global', '4'], ['tcj_global', '5'], ['preferida', 'tcj'], ['comp_primeiro', 'tcj']]) await nota(p, n, v);
  verificar(!(await p.$eval('form', f => f.checkValidity())), 'sem nome e email, o formulário não é válido');
  await p.click('.campo:has(#degustacao-gostou) .ditado__botao');
  await p.evaluate(() => window.__recs[0].emit(['adorei a apresentação']));
  await p.click('.campo:has(#degustacao-gostou) .ditado__botao');
  verificar(await p.inputValue('#degustacao-gostou') === 'Adorei a apresentação', 'ditado escreve no campo de texto');
  await p.fill('#degustacao-nome', 'Ana Teste');
  await p.fill('#degustacao-email', mail('ana'));
  await espera(3200);
  let antes = caixa();
  await p.click('button[type=submit]');
  await p.waitForSelector('#opiniao-enviada.visivel', { timeout: 15000 });
  let em = await novos(antes, 2);
  verificar(em.length === 2, 'degustação (pt): dois emails');
  verificar(em.some(e => /RCPT TO:<info@tantielorraine\.pt>/.test(e.envelope) && !/gadgetmetodo/.test(e.envelope)), 'opinião só para info@');
  verificar(em.some(e => new RegExp('RCPT TO:<' + mail('ana')).test(e.envelope) && /obrigado pela sua opinião/.test(e.assunto)), 'agradecimento em português para quem respondeu');

  // Inglês
  const e = await ctx.newPage();
  await e.goto(BASE + '/en/tasting.html', { waitUntil: 'networkidle' });
  await e.fill('[name=local]', 'At home');
  await e.check('input[name="provou[]"][value=rpcl]');
  await nota(e, 'rpcl_global', '4');
  await e.fill('[name=nome]', 'John Test');
  await e.fill('[name=email]', mail('john'));
  await espera(3200);
  antes = caixa();
  await e.click('button[type=submit]');
  await e.waitForSelector('#opiniao-enviada.visivel', { timeout: 15000 });
  em = await novos(antes, 2);
  verificar(em.some(x => new RegExp(mail('john')).test(x.envelope) && /thank you for your feedback/.test(x.assunto)), 'agradecimento em inglês');

  // Espanhol sem JavaScript: erro sem email, envio completo redireciona para a mensagem de sucesso
  const semJs = await b.newContext({ javaScriptEnabled: false });
  const s = await semJs.newPage();
  const preencher = async (comEmail) => {
    await s.goto(BASE + '/es/degustacion.html');
    await s.fill('[name=local]', 'Feria de Vigo');
    await s.check('input[name="provou[]"][value=acj]');
    await s.check('input[name=acj_global][value="5"]', { force: true });
    await s.fill('[name=nome]', 'Lucía Test');
    if (comEmail) await s.fill('[name=email]', mail('lucia'));
    else await s.$eval('[name=email]', i => i.removeAttribute('required'));
    await s.click('button[type=submit]');
    await s.waitForLoadState();
  };
  await preencher(false);
  verificar(s.url().endsWith('#opiniao-erro'), 'sem JavaScript e sem email: mensagem de erro');
  antes = caixa();
  await preencher(true);
  verificar(s.url().endsWith('#opiniao-enviada'), 'sem JavaScript: opinião enviada');
  em = await novos(antes, 2);
  verificar(em.some(x => new RegExp(mail('lucia')).test(x.envelope) && /gracias por su opinión/.test(x.assunto)), 'agradecimento em espanhol');

  // Contactos: aviso para info@ e confirmação para quem escreveu
  const k = await ctx.newPage();
  await k.goto(BASE + '/contactos.html', { waitUntil: 'networkidle' });
  await k.fill('#pedido-nome', 'Ana Teste');
  await k.fill('#pedido-email', mail('ana2'));
  await k.fill('#pedido-mensagem', 'Pedido de teste do formulário.');
  await espera(3200);
  antes = caixa();
  await k.click('button[type=submit]');
  await k.waitForSelector('#pedido-enviado.visivel', { timeout: 15000 });
  em = await novos(antes, 2);
  verificar(em.some(x => /RCPT TO:<info@tantielorraine\.pt>/.test(x.envelope) && /Pedido de informação/.test(x.assunto)), 'contactos: pedido para info@');
  verificar(em.some(x => new RegExp(mail('ana2')).test(x.envelope)), 'contactos: confirmação para quem escreveu');

  verificar(erros.length === 0, 'sem erros de JavaScript' + (erros.length ? ': ' + erros.join(' | ') : ''));
  await b.close();
  console.log(falhas ? `\n${falhas} verificação(ões) falharam.` : '\nTodas as verificações passaram.');
  process.exit(falhas ? 1 : 0);
})();
