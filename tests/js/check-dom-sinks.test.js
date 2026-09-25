import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { afterEach, beforeEach, test } from 'node:test';

/** @type {string} */
let dir;

beforeEach(() => {
  dir = mkdtempSync(join(tmpdir(), 'spreadshop-sinks-'));
});

afterEach(() => {
  rmSync(dir, { recursive: true, force: true });
});

/**
 * @param {string} source
 */
function runGateOn(source) {
  writeFileSync(join(dir, 'sample.js'), source);
  return spawnSync(process.execPath, ['scripts/check-dom-sinks.js', dir], { encoding: 'utf8' });
}

for (const sink of [
  'el.innerHTML = x;',
  'el.outerHTML = x;',
  'el.insertAdjacentHTML("beforeend", x);',
  'document.write(x);',
  'document.writeln(x);',
]) {
  test(`fails on ${sink}`, () => {
    const result = runGateOn(sink);
    assert.equal(result.status, 1);
    assert.match(result.stderr, /sample\.js:1:/);
  });
}

test('passes on textContent and createElement', () => {
  const result = runGateOn('el.textContent = x;\nel.append(document.createElement("span"));');
  assert.equal(result.status, 0);
});
