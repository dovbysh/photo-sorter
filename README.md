# photo_sorter
## Installation
https://github.com/dovbysh/photo-sorter/releases/download/v0.1/photo_sorter.phar - compiled all-in-one

From source:

Clone or download. Install phar-composer https://github.com/clue/phar-composer.
```
make
```

### Docker
```
docker run -it -v SOURCE_DIRECTORY:/psrc -v DESTINATION_DIR:/pdst --user $UID photo_sorter
```
