"""Package the app for a Hostinger subdomain inside the main public_html."""
import argparse
from pathlib import Path
import re
from zipfile import ZipFile, ZIP_DEFLATED

parser = argparse.ArgumentParser()
parser.add_argument('--subdomain', required=True)
parser.add_argument('--output', type=Path, required=True)
args = parser.parse_args()
if not re.fullmatch(r'[a-z0-9][a-z0-9-]{0,62}', args.subdomain):
    parser.error('Invalid subdomain folder')
root = Path(__file__).resolve().parents[1]
private_files = ['.htaccess', 'archive.php', 'auth-view.php', 'bootstrap.php',
                 'config.example.php', 'repository.php', 'schema.sql', 'onedrive.php', 'model-storage.php', 'recovery.php']
public_files = ['.htaccess', 'api.php', 'index.php', 'login.php', 'logout.php', 'setup.php', 'storage.js',
                'onedrive.php', 'onedrive-callback.php', 'onedrive-migrate.php', 'recover.php']
args.output.parent.mkdir(parents=True, exist_ok=True)
with ZipFile(args.output, 'w', ZIP_DEFLATED) as bundle:
    for name in private_files:
        bundle.write(root / '3dlens-private' / name, '3dlens-private/' + name)
    for name in public_files:
        data = (root / 'public_html' / name).read_bytes()
        if name.endswith('.php'):
            # /domain/public_html/koomen -> /domain/3dlens-private.
            # Private files must also be outside the main domain's documentroot.
            text = data.decode('utf-8')
            assert "dirname(__DIR__)" in text, name
            data = text.replace('dirname(__DIR__)', 'dirname(__DIR__, 2)').encode('utf-8')
        bundle.writestr('public_html/' + args.subdomain + '/' + name, data)
with ZipFile(args.output) as bundle:
    assert bundle.testzip() is None
    assert len(bundle.namelist()) == len(private_files) + len(public_files)
    assert not any('config.local.php' in name or '/storage/' in name for name in bundle.namelist())
    index = bundle.read(f'public_html/{args.subdomain}/index.php').decode('utf-8')
    assert "dirname(__DIR__, 2) . '/3dlens-private/bootstrap.php'" in index
print(f'Verified Hostinger subdomain package: {args.output.name}; no credentials or model data.')
