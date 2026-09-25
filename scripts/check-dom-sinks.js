/**
 * Fails when shipped JavaScript writes markup through an HTML-parsing sink.
 *
 * Everything the front end injects comes from Spreadshirt's servers, so any path that parses
 * a string as HTML is an XSS path. Text goes through textContent and elements through
 * createElement; this gate keeps it that way.
 */
import { readdirSync, readFileSync } from 'node:fs';
import { join } from 'node:path';

const root = process.argv[2] ?? 'spreadshop/js';
const sinks = /\b(innerHTML|outerHTML|insertAdjacentHTML|document\.write(ln)?)\b/;

/** @type {string[]} */
const hits = [];
for (const file of readdirSync(root, { recursive: true, encoding: 'utf8' })) {
  if (!file.endsWith('.js')) {
    continue;
  }
  const path = join(root, file);
  readFileSync(path, 'utf8')
    .split('\n')
    .forEach((line, index) => {
      if (sinks.test(line)) {
        hits.push(`${path}:${index + 1}: ${line.trim()}`);
      }
    });
}

if (hits.length > 0) {
  console.error('HTML-parsing DOM sinks found in shipped JavaScript:');
  for (const hit of hits) {
    console.error(`  ${hit}`);
  }
  process.exit(1);
}
console.log(`No HTML-parsing DOM sinks in ${root}.`);
