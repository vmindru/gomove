#!/usr/bin/env python3
"""Create local Compose credentials without overwriting existing settings."""

import base64
import os
from pathlib import Path
import secrets


def main() -> None:
    root = Path(__file__).resolve().parent.parent
    destination = root / '.env.compose'
    template = (root / '.env.compose.example').read_text()
    contents = template.replace(
        '__GENERATE_APP_KEY__', 'base64:' + base64.b64encode(secrets.token_bytes(32)).decode()
    ).replace('__GENERATE_DB_PASSWORD__', secrets.token_hex(32))
    try:
        descriptor = os.open(destination, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
    except FileExistsError:
        print('.env.compose already exists; existing settings preserved.')
        return
    with os.fdopen(descriptor, 'w') as output:
        output.write(contents)
    print('Created .env.compose with mode 0600. Secret values are not displayed.')


if __name__ == '__main__':
    main()
