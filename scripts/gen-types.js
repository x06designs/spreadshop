/**
 * Generates types/layout.d.ts from the layout settings schema.
 *
 * The schema is the contract between the PHP that stores the options and the JavaScript that
 * reads them. With --check this writes nothing and fails when the committed types no longer
 * match the schema, so the two sides cannot drift silently.
 */
import { readFileSync, writeFileSync } from 'node:fs';
import { compile } from 'json-schema-to-typescript';

const schemaPath = 'spreadshop/schema/layout.schema.json';
const typesPath = 'types/layout.d.ts';

const schema = JSON.parse(readFileSync(schemaPath, 'utf8'));
const generated = await compile(schema, 'SpreadshopLayoutSettings', {
  bannerComment: `/* Generated from ${schemaPath} by scripts/gen-types.js. Do not edit. */`,
  additionalProperties: false,
  format: false,
});

if (process.argv.includes('--check')) {
  /** @type {string} */
  let committed = '';
  try {
    committed = readFileSync(typesPath, 'utf8');
  } catch {
    committed = '';
  }
  if (committed !== generated) {
    console.error(`${typesPath} is out of date with ${schemaPath}. Run: npm run gen:types`);
    process.exit(1);
  }
  console.log(`${typesPath} matches ${schemaPath}.`);
} else {
  writeFileSync(typesPath, generated);
  console.log(`Wrote ${typesPath}.`);
}
