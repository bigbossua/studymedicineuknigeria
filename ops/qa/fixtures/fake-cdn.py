"""A stand-in for the site behind a CDN, for ops/qa/sitemap-probe-selftest.sh. Modes: open (everyone served),
block (a user agent claiming Googlebot gets 403, as the Hostinger CDN does to GitHub's addresses since 2026-10-27),
all (everyone gets 403). Usage: python3 fake-cdn.py MODE PORT"""
import http.server, sys
PORT = int(sys.argv[2])
B = f'http://127.0.0.1:{PORT}'
BLOCK = sys.argv[1] == 'block'
SM = f'<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>{B}/</loc></url><url><loc>{B}/a</loc></url></urlset>'
class H(http.server.BaseHTTPRequestHandler):
    def log_message(self, *a): pass
    def send(self, code, ctype, body):
        self.send_response(code); self.send_header('Content-Type', ctype); self.send_header('Content-Length', str(len(body))); self.end_headers()
        if self.command != 'HEAD': self.wfile.write(body)
    def do_GET(self):
        if sys.argv[1] == 'all': return self.send(403, 'text/html', b'denied')
        if BLOCK and 'Googlebot' in self.headers.get('User-Agent', ''): return self.send(403, 'text/html', b'denied')
        p = self.path
        if p == '/sitemap.xml': return self.send(200, 'application/xml', SM.encode())
        if p == '/robots.txt': return self.send(200, 'text/plain', f'User-agent: *\nDisallow:\nSitemap: {B}/sitemap.xml\n'.encode())
        return self.send(200, 'text/html', f'<html><head><link rel="canonical" href="{B}{p}"></head></html>'.encode())
    do_HEAD = do_GET
http.server.HTTPServer(('127.0.0.1', PORT), H).serve_forever()
