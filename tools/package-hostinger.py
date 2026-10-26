"""Package the app for a Hostinger subdomain inside the main public_html."""
import argparse
from pathlib import Path
import re
from zipfile import ZipFile, ZIP_DEFLATED

parser = argparse.ArgumentParser()
parser.add_argument('--subdomain', help='Optional subdomain directory inside public_html')
parser.add_argument('--output', type=Path, required=True)
args = parser.parse_args()
if args.subdomain and not re.fullmatch(r'[a-z0-9][a-z0-9-]{0,62}', args.subdomain):
    parser.error('Invalid subdomain folder')
root = Path(__file__).resolve().parents[1]
private_files = ['.htaccess', 'archive.php', 'auth-view.php', 'bootstrap.php', 'release.php', 'changelog-view.php',
                 'config.example.php', 'repository.php', 'schema.sql', 'onedrive.php', 'model-storage.php', 'recovery.php', 'mail.php',
                 'projects.php', 'migrations/003-projects.sql', 'migrations/002-email-reset.sql', 'vendor/phpmailer/PHPMailer.php', 'vendor/phpmailer/SMTP.php',
                 'vendor/phpmailer/Exception.php', 'vendor/phpmailer/LICENSE']
public_files = ['.htaccess', 'api.php', 'index.php', 'login.php', 'logout.php', 'setup.php', 'storage.js', 'projects.js', 'model-analysis.js', 'changelog.php',
                'onedrive.php', 'onedrive-callback.php', 'onedrive-migrate.php', 'recover.php',
                'account.php', 'forgot-password.php', 'reset-password.php']
public_prefix = 'public_html/' + (args.subdomain + '/' if args.subdomain else '')
args.output.parent.mkdir(parents=True, exist_ok=True)
with ZipFile(args.output, 'w', ZIP_DEFLATED) as bundle:
    for name in private_files:
        bundle.write(root / '3dlens-private' / name, '3dlens-private/' + name)
    for name in public_files:
        data = (root / 'public_html' / name).read_bytes()
        if name.endswith('.php') and args.subdomain:
            # /domain/public_html/koomen -> /domain/3dlens-private.
            # Private files must also be outside the main domain's documentroot.
            text = data.decode('utf-8')
            assert "dirname(__DIR__)" in text, name
            data = text.replace('dirname(__DIR__)', 'dirname(__DIR__, 2)').encode('utf-8')
        bundle.writestr(public_prefix + name, data)
    bundle.write(root / 'distribution' / 'INSTALLEREN.html', 'INSTALLEREN.html')
    bundle.write(root / 'distribution' / 'LEESMIJ.txt', 'LEESMIJ.txt')
with ZipFile(args.output) as bundle:
    assert bundle.testzip() is None
    assert len(bundle.namelist()) == len(private_files) + len(public_files) + 2
    assert not any('config.local.php' in name or '/storage/' in name for name in bundle.namelist())
    index = bundle.read(public_prefix + 'index.php').decode('utf-8')
    expected = 'dirname(__DIR__, 2)' if args.subdomain else 'dirname(__DIR__)'
    assert expected + " . '/3dlens-private/bootstrap.php'" in index
    forbidden = [b'u179310343', b'koomen.3dlens.cloud', b'jeroen_koomen', b'jeroen@jroen.nl', b'6869614d-dfe2-4b8e-aa41-1afc90884e0d']
    for name in bundle.namelist():
        assert not any(value in bundle.read(name) for value in forbidden), f'Installation-specific data found: {name}'
print(f'Verified Hostinger subdomain package: {args.output.name}; no credentials or model data.')
