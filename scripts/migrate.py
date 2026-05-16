#!/usr/bin/env python3
"""
Migration script for tank_data.js.

Usage:
    python3 scripts/migrate.py                        # migrates data/tank_data.js
    python3 scripts/migrate.py /path/to/tank_data.js  # migrates a specific file

When adding a new schema version:
    1. Write a migrate_vN_to_vN1() function below
    2. Register it in MIGRATIONS with the target version as the key
    3. Update CURRENT_VERSION
    4. Update templates/tank_data.template.js to reflect the new structure
    5. Bump SCHEMA_VERSION in templates/tank_data.template.js
"""

import json
import re
import sys
import shutil
from datetime import datetime
from pathlib import Path

CURRENT_VERSION = 2

# ---------------------------------------------------------------------------
# Migrations
# ---------------------------------------------------------------------------

def migrate_v0_to_v1(data):
    """Remove legacy top-level equipment/log arrays; add dose and blog to each tank."""
    for key in ['equipment', 'log']:
        data.pop(key, None)

    for tank in _tanks(data):
        data[tank].setdefault('dose', [])
        data[tank].setdefault('blog', [])

    data['_schemaVersion'] = 1
    return data


def migrate_v1_to_v2(data):
    """Add magnesium array and latest field to each tank."""
    for tank in _tanks(data):
        data[tank]['latest'].setdefault('magnesium', None)
        data[tank].setdefault('magnesium', [])

    data['_schemaVersion'] = 2
    return data


MIGRATIONS = {
    1: migrate_v0_to_v1,
    2: migrate_v1_to_v2,
}

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

def _tanks(data):
    return [k for k in data if not k.startswith('_')]

def load(path):
    content = path.read_text()
    json_str = re.sub(r'^const RAW\s*=\s*', '', content.strip()).rstrip(';')
    return json.loads(json_str)

def save(path, data):
    path.write_text('const RAW = ' + json.dumps(data, indent=2) + ';')

def backup(path):
    ts = datetime.now().strftime('%Y%m%d_%H%M%S')
    dest = path.parent / f'tank_data_backup_{ts}.js'
    shutil.copy(path, dest)
    print(f'  Backup: {dest.name}')

# ---------------------------------------------------------------------------
# Runner
# ---------------------------------------------------------------------------

def main():
    args = [a for a in sys.argv[1:] if not a.startswith('--')]
    check_only = '--check' in sys.argv

    project_root = Path(__file__).parent.parent
    config_file  = project_root / 'reefdash.json'
    cfg = json.loads(config_file.read_text()) if config_file.exists() else {}
    default = project_root / cfg.get('tankData', 'data/tank_data.js')
    path = Path(args[0]) if args else default

    if not path.exists():
        print(f'ERROR: {path} not found.', file=sys.stderr)
        sys.exit(1)

    data = load(path)
    current = data.get('_schemaVersion', 0)

    print(f'File:            {path}')
    print(f'Current version: v{current}')
    print(f'Target version:  v{CURRENT_VERSION}')

    if current == CURRENT_VERSION:
        print('Already up to date.')
        return

    if current > CURRENT_VERSION:
        print('ERROR: data is newer than this script. Update the script first.', file=sys.stderr)
        sys.exit(1)

    if check_only:
        print(f'Migration required: v{current} → v{CURRENT_VERSION}')
        sys.exit(1)

    backup(path)

    for version in range(current + 1, CURRENT_VERSION + 1):
        if version not in MIGRATIONS:
            print(f'ERROR: no migration defined for v{version - 1} → v{version}.', file=sys.stderr)
            sys.exit(1)
        print(f'  Applying v{version - 1} → v{version}...')
        data = MIGRATIONS[version](data)

    save(path, data)
    print(f'Done. Schema is now v{CURRENT_VERSION}.')

if __name__ == '__main__':
    main()
