"""Route modules for unitxt.

Each module exposes one or more APIRouter instances that get mounted on the
main `api` router in `server.py`. Modules import shared infrastructure
(db client, auth dependencies, helpers) from `server` AFTER server.py has
finished defining them — so the import statements in this package live at
the bottom of `server.py`, not the top.
"""
