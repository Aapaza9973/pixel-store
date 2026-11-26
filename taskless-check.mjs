#!/usr/bin/env node
// Ejecuta las reglas de Taskless del proyecto.
//
//   node .taskless/bin/cli.mjs check            (escaneo completo)
//   npm run taskless -- app/Services            (solo esas rutas)
//
// El launcher fija la version del CLI registrada en .taskless/taskless.json;
// el check no necesita login ni red. Sale con 1 si una regla de severidad
// error encuentra algo, asi que sirve tal cual en CI.
import { spawnSync } from "node:child_process";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";

const root = dirname(fileURLToPath(import.meta.url));
const launcher = join(root, ".taskless", "bin", "cli.mjs");

const result = spawnSync(
  process.execPath,
  [launcher, "check", ...process.argv.slice(2)],
  { stdio: "inherit", cwd: root }
);

if (result.error !== undefined) {
  console.error(`taskless: no se pudo iniciar ${launcher}: ${result.error.message}`);
  process.exit(1);
}

process.exit(result.status ?? 1);