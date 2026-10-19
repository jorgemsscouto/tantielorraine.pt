# Servidor de email (SMTP) falso para testar os formulários sem enviar emails reais. TLS direto na porta 4651 e
# STARTTLS na 2525, autenticação PLAIN e LOGIN; cada mensagem fica num ficheiro .eml na caixa, com uma primeira linha
# "X-Envelope:" com o remetente e os destinatários do envelope.
# Uso: python3 -I smtp_falso.py <utilizador> <palavra-passe> <pasta do certificado> <pasta da caixa>
import base64, os, socketserver, ssl, sys, threading, time

UTIL, PASSE, CERT, CAIXA = sys.argv[1:5]
ctx = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
ctx.load_cert_chain(os.path.join(CERT, 'cert.pem'), os.path.join(CERT, 'chave.pem'))
os.makedirs(CAIXA, exist_ok=True)


class Sessao(socketserver.StreamRequestHandler):
    def setup(self):
        if self.server.server_address[1] == 4651:
            self.request = ctx.wrap_socket(self.request, server_side=True)
        super().setup()

    def env(self, linha):
        self.wfile.write((linha + '\r\n').encode())
        self.wfile.flush()

    def ler(self):
        return self.rfile.readline().decode('utf-8', 'replace').rstrip('\r\n')

    def handle(self):
        self.env('220 teste ESMTP')
        autenticado, rcpt, de = False, [], None
        while True:
            linha = self.ler()
            if not linha:
                return
            cmd = linha.split(' ', 1)[0].upper()
            if cmd == 'EHLO':
                self.env('250-teste')
                if self.server.server_address[1] == 2525 and not isinstance(self.request, ssl.SSLSocket):
                    self.env('250-STARTTLS')
                self.env('250-AUTH LOGIN PLAIN')
                self.env('250 8BITMIME')
            elif cmd == 'STARTTLS':
                self.env('220 pronto')
                self.request = ctx.wrap_socket(self.request, server_side=True)
                self.rfile = self.request.makefile('rb')
                self.wfile = self.request.makefile('wb')
            elif linha.upper().startswith('AUTH PLAIN '):
                _, u, p = base64.b64decode(linha[11:]).decode().split('\0')
                autenticado = (u, p) == (UTIL, PASSE)
                self.env('235 ok' if autenticado else '535 credenciais erradas')
            elif linha.upper() == 'AUTH LOGIN':
                self.env('334 VXNlcm5hbWU6')
                u = base64.b64decode(self.ler()).decode()
                self.env('334 UGFzc3dvcmQ6')
                p = base64.b64decode(self.ler()).decode()
                autenticado = (u, p) == (UTIL, PASSE)
                self.env('235 ok' if autenticado else '535 credenciais erradas')
            elif cmd == 'MAIL':
                if not autenticado:
                    self.env('530 autenticar primeiro')
                    continue
                de = linha
                self.env('250 ok')
            elif cmd == 'RCPT':
                rcpt.append(linha)
                self.env('250 ok')
            elif cmd == 'DATA':
                self.env('354 envie')
                linhas = []
                while True:
                    x = self.rfile.readline().decode()
                    if x == '.\r\n':
                        break
                    linhas.append(x[1:] if x.startswith('..') else x)
                with open(os.path.join(CAIXA, f'{time.time():.6f}.eml'), 'w') as f:
                    f.write(f"X-Envelope: {de} | {' | '.join(rcpt)}\r\n" + ''.join(linhas))
                self.env('250 guardada')
                rcpt, de = [], None
            elif cmd == 'QUIT':
                self.env('221 adeus')
                return
            else:
                self.env('500 ?')


class Servidor(socketserver.ThreadingTCPServer):
    allow_reuse_address = True
    daemon_threads = True


for porta in (4651, 2525):
    threading.Thread(target=Servidor(('127.0.0.1', porta), Sessao).serve_forever, daemon=True).start()
print('SMTP de teste pronto', flush=True)
threading.Event().wait()
