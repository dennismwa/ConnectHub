// Builds assets/icons/sprite.svg from the Lucide icons listed in tools/icons.txt
// Usage: node tools/build-icons.js
const fs = require('fs');
const path = require('path');
const root = path.resolve(__dirname, '..');
const dir = path.join(root, 'node_modules', 'lucide-static', 'icons');
const names = fs.readFileSync(path.join(__dirname, 'icons.txt'), 'utf8')
  .split('\n').map(s => s.trim()).filter(s => s && !s.startsWith('#'));
const aliases = { 'help-circle': 'circle-help', 'check-circle': 'circle-check', 'x-circle': 'circle-x', 'alert-circle': 'circle-alert', 'alert-triangle': 'triangle-alert', 'edit': 'square-pen', 'edit-3': 'pen-line', 'plus-circle': 'circle-plus', 'bar-chart-3': 'chart-column', 'pie-chart': 'chart-pie', 'grid': 'grid-3x3', 'layout-grid': 'layout-grid', 'twitter': 'twitter', 'facebook': 'facebook', 'instagram': 'instagram', 'youtube': 'youtube', 'user-round': 'user-round', 'users-round': 'users-round', 'settings-2': 'settings-2', 'loader-2': 'loader-circle', 'more-horizontal': 'ellipsis', 'more-vertical': 'ellipsis-vertical', 'home': 'house', 'cog': 'cog', 'share': 'share', 'filter': 'funnel', 'trending-up': 'trending-up', 'trending-down': 'trending-down', 'trash': 'trash', 'message-square-heart': 'message-square-heart', 'sparkle': 'sparkle', 'wand-sparkles': 'wand-sparkles', 'map-pinned': 'map-pinned', 'sun-moon': 'sun-moon', 'toggle-left': 'toggle-left', 'toggle-right': 'toggle-right', 'circle-check': 'circle-check', 'circle-x': 'circle-x', 'circle-help': 'circle-help', 'circle-dollar-sign': 'circle-dollar-sign' };
const custom = {
  football: '<circle cx="12" cy="12" r="9.5"/><path d="m12 7.5 3.8 2.8-1.5 4.4H9.7l-1.5-4.4z"/><path d="M12 7.5V2.6M15.8 10.3l4.6-1.5M14.3 14.7l2.9 3.9M9.7 14.7l-2.9 3.9M8.2 10.3 3.6 8.8"/>',
  basketball: '<circle cx="12" cy="12" r="9.5"/><path d="M2.5 12h19M12 2.5v19M5.2 5.2c3.2 3.2 3.2 10.4 0 13.6M18.8 5.2c-3.2 3.2-3.2 10.4 0 13.6"/>',
  matches: '<path transform="translate(.68 1.02) scale(.66)" stroke-width="2.7" d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/><path transform="translate(7.48 7.14) scale(.66)" stroke-width="2.7" d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
  facebook: '<path d="M13.5 21v-7.5h2.6l.4-3h-3V8.6c0-.9.3-1.5 1.5-1.5h1.6V4.4c-.3 0-1.2-.1-2.3-.1-2.3 0-3.9 1.4-3.9 4v2.2H7.8v3h2.6V21z" fill="currentColor" stroke="none"/>',
  instagram: '<rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/>',
  twitter: '<path d="M4 4l7.2 9.6L4.4 20h1.8l5.8-5.9L16.6 20H20l-7.5-10 6.4-6h-1.7l-5.3 5.4L8.8 4z" fill="currentColor" stroke="none"/>',
  youtube: '<path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><path d="m10 15 5-3-5-3z"/>',
  tiktok: '<path d="M16.5 3c.3 2.2 1.7 3.8 3.9 4v3a7 7 0 0 1-3.9-1.3v6.3a5.5 5.5 0 1 1-5.5-5.5c.3 0 .6 0 .9.1v3.1a2.5 2.5 0 1 0 1.6 2.3V3z" fill="currentColor" stroke="none"/>',
};
const missing = [];
let out = '<svg xmlns="http://www.w3.org/2000/svg" style="display:none">\n';
const seen = new Set();
for (const name of names) {
  if (seen.has(name)) continue;
  seen.add(name);
  if (custom[name]) { out += `<symbol id="i-${name}" viewBox="0 0 24 24">${custom[name]}</symbol>\n`; continue; }
  let file = path.join(dir, name + '.svg');
  if (!fs.existsSync(file) && aliases[name]) file = path.join(dir, aliases[name] + '.svg');
  if (!fs.existsSync(file)) { missing.push(name); continue; }
  const svg = fs.readFileSync(file, 'utf8');
  const inner = svg.replace(/^[\s\S]*?<svg[^>]*>/, '').replace(/<\/svg>[\s\S]*$/, '').replace(/\s+/g, ' ').trim();
  out += `<symbol id="i-${name}" viewBox="0 0 24 24">${inner}</symbol>\n`;
}
out += '</svg>\n';
fs.mkdirSync(path.join(root, 'assets', 'icons'), { recursive: true });
fs.writeFileSync(path.join(root, 'assets', 'icons', 'sprite.svg'), out);
console.log(`sprite.svg: ${seen.size - missing.length} icons, ${(out.length / 1024).toFixed(1)} KB`);
if (missing.length) console.log('MISSING (not in lucide-static):', missing.join(', '));
