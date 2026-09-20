import fs from 'node:fs';
import path from 'node:path';

// PHP accepts the file for 30 seconds; refresh it while the server is alive.
export default function hot() {
  let hotFile;
  let origin;
  let heartbeat;
  const signals = ['SIGINT', 'SIGTERM', 'SIGHUP'];

  const clean = () => {
    clearInterval(heartbeat);
    if (origin && fs.existsSync(hotFile) && fs.readFileSync(hotFile, 'utf8') === origin) {
      fs.rmSync(hotFile);
    }
    process.off('exit', clean);
    for (const signal of signals) process.off(signal, stop);
  };
  const stop = () => {
    clean();
    process.exit(0);
  };

  return {
    name: 'forage:hot-file',
    apply: 'serve',
    configResolved(config) {
      hotFile = path.join(config.root, '.vite/hot');
    },
    configureServer(server) {
      server.httpServer?.once('listening', () => {
        const address = server.httpServer.address();
        if (!address || typeof address === 'string') return;

        const configuredHost = server.config.server.host;
        let host = typeof configuredHost === 'string' ? configuredHost : 'localhost';
        if (host === '0.0.0.0' || host === '::') host = 'localhost';
        if (host.includes(':')) host = `[${host}]`;
        origin = server.config.server.origin?.replace(/\/$/, '')
          || `${server.config.server.https ? 'https' : 'http'}://${host}:${address.port}`;

        fs.mkdirSync(path.dirname(hotFile), { recursive: true });
        fs.writeFileSync(hotFile, origin);
        heartbeat = setInterval(() => {
          if (fs.existsSync(hotFile) && fs.readFileSync(hotFile, 'utf8') === origin) {
            const now = new Date();
            fs.utimesSync(hotFile, now, now);
          }
        }, 10_000);
        heartbeat.unref();
        process.once('exit', clean);
        for (const signal of signals) process.once(signal, stop);
        server.httpServer.once('close', clean);
      });
    },
    closeBundle: clean,
  };
}
