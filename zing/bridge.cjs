const crypto = require('node:crypto');

function validId(value) {
  return typeof value === 'string' && /^[A-Za-z0-9_-]{1,40}$/.test(value);
}

function mapSongs(response) {
  if (response?.err !== 0) throw Object.assign(new Error('Zing MP3 chưa trả kết quả. Hãy thử lại.'), { status: 502 });
  return (response.data?.songs || []).filter(song => validId(song.encodeId) &&
    song.streamingStatus === 1 && typeof song.title === 'string').slice(0, 50).map(song => ({
      id: -(crypto.createHash('sha256').update(song.encodeId).digest().readUInt32BE(0) & 0x3fffffff) - 1,
      title: song.title,
      artist: song.artistsNames || null,
      composer: null,
      album: song.album?.title || null,
      duration: String(song.duration || 0),
      lyrics: null,
      audio_url: `stream?id=${encodeURIComponent(song.encodeId)}`,
      cover_url: song.thumbnailM || song.thumbnail || null,
      source: 'zingmp3',
      source_id: song.encodeId,
    }));
}

function streamUrl(response) {
  if (response?.err !== 0) {
    throw Object.assign(new Error('Bài này hiện không phát được từ Zing MP3 hoặc đang bị giới hạn.'), { status: 403 });
  }
  const value = response.data?.['128'];
  let url;
  try { url = new URL(value); } catch { /* VIP markers are not stream URLs. */ }
  if (!url || url.protocol !== 'https:' || url.username || url.password ||
      !(url.hostname === 'zmdcdn.me' || url.hostname.endsWith('.zmdcdn.me') ||
        url.hostname === 'zingmp3.vn' || url.hostname.endsWith('.zingmp3.vn'))) {
    throw Object.assign(new Error('Bài này chưa có đường dẫn nghe miễn phí khả dụng.'), { status: 403 });
  }
  return url.href;
}

function mapLyrics(response) {
  const lines = response?.err === 0 ? (response.data?.sentences || []).flatMap(sentence => {
    const words = sentence.words || [];
    const start = words[0]?.startTime;
    const text = words.map(word => word.data || '').join(' ').trim();
    if (!Number.isFinite(start) || start < 0 || !text) return [];
    const minutes = Math.floor(start / 60000);
    const seconds = Math.floor(start / 1000) % 60;
    const fraction = Math.floor(start / 10) % 100;
    return [`[${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}.${String(fraction).padStart(2, '0')}]${text}`];
  }) : [];
  return { status: lines.length ? 'ready' : 'unsupported', lyrics_lrc: lines.join('\n'), error: null };
}

module.exports = { validId, mapSongs, streamUrl, mapLyrics };
