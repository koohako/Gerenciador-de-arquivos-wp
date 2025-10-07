import os
import zipfile
import sys

PLUGIN_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
PLUGIN_NAME = "gerenciador-arquivos-pro"

# Permite passar versão como argumento opcional
version = sys.argv[1] if len(sys.argv) > 1 else ""
if version:
    ZIP_NAME = f"{PLUGIN_NAME}-{version}.zip"
else:
    ZIP_NAME = f"{PLUGIN_NAME}.zip"
ZIP_PATH = os.path.join(os.path.dirname(PLUGIN_DIR), ZIP_NAME)

with zipfile.ZipFile(ZIP_PATH, "w", zipfile.ZIP_DEFLATED) as zipf:
    for root, dirs, files in os.walk(PLUGIN_DIR):
        # Ignora pastas desnecessárias
        if "dev" in dirs:
            dirs.remove("dev")
        if ".git" in dirs:
            dirs.remove(".git")
        for file in files:
            abs_path = os.path.join(root, file)
            rel_path = os.path.relpath(abs_path, PLUGIN_DIR)
            zipf.write(abs_path, os.path.join(PLUGIN_NAME, rel_path))

print(f"ZIP criado: {ZIP_PATH}")
