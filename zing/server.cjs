const http = require('node:http');
const axios = require('axios');
const { ZingMp3 } = require('zingmp3-api-full');
const { validId, mapSongs, streamUrl, mapLyrics } = require('./bridge.cjs');

axios.defaults.timeout = 10000;
const port = Number(process.env.MUSICBOX_ZING_PORT || 8877);
const host = process.env.MUSICBOX_ZING_HOST || '127.0.0.1';

async function upstream(operation) {
  ZingMp3.CTIME = String(Math.floor(Date.now() / 1000));
  let timer;
  try {
    return await Promise.race([
      operation(),
      new Promise((_, reject) => { timer = setTimeout(() => reject(Object.assign(
        new Error('Zing MP3 phản hồi quá lâu. Hãy thử lại.'), { status: 504 })), 12000); }),
    ]);
  } finally { clearTimeout(timer); }
}

function json(response, status, data) {
  response.writeHead(status, { 'Content-Type': 'application/json; charset=utf-8', 'Cache-Control': 'no-store' });
  response.end(JSON.stringify(data));
}

const server = http.createServer(async (request, response) => {
  try {
    if (request.method !== 'GET' && request.method !== 'HEAD') return json(response, 405, { message: 'Chỉ hỗ trợ GET.' });
    const url = new URL(request.url, 'http://localhost');
    if (url.pathname === '/health') return json(response, 200, { status: 'ok', source: 'zingmp3', version: 1 });
    if (url.pathname === '/search') {
      const query = (url.searchParams.get('q') || '').trim();
      if (!query || query.length > 120) return json(response, 400, { message: 'Nhập từ khóa từ 1 đến 120 ký tự.' });
      return json(response, 200, mapSongs(await upstream(() => ZingMp3.search(query))));
    }
    const id = url.searchParams.get('id');
    if (!['/stream', '/lyrics'].includes(url.pathname)) return json(response, 404, { message: 'Không có đường dẫn này.' });
    if (!validId(id)) return json(response, 400, { message: 'ID bài hát không hợp lệ.' });
    if (url.pathname === '/lyrics') return json(response, 200, mapLyrics(await upstream(() => ZingMp3.getLyric(id))));
    const location = streamUrl(await upstream(() => ZingMp3.getSong(id)));
    // Resolve a fresh URL at playback time, including after saving a favorite.
    // Audio is delivered directly by the provider; this server stores no audio.
    response.writeHead(302, { Location: location, 'Cache-Control': 'no-store' });
    response.end();
  } catch (error) {
    json(response, error.status || 502, { message: error.status ? error.message : 'Chưa kết nối được Zing MP3. Hãy thử lại.' });
  }
});
server.listen(port, host, () => console.log(`MusicBox Zing bridge: http://${host}:${port}/`));
server.on('error', error => { console.error(error.code || error.name); process.exitCode = 1; });
