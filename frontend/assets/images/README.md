# Images

Each model has its own folder. If a file listed in the data is missing, the website shows a "Photo coming soon" placeholder in its place, so nothing breaks.

Only use photos you own or have permission to use. Do not use stock photos presented as your own completed projects.

| Folder / file | Used on |
|---|---|
| `atlantic/`, `norway/`, `scandinavia/`, `continental/` | Model cards, detail pages and their galleries |
| `hero.jfif` | Home page, large image at the top (set in `frontend/views/home.html`) |
| `about.jfif` | Home page, "About A-Frame Living" section (set in `frontend/views/home.html`) |

## Which photos appear, and in what order

The website does not scan folders automatically. Each photo is listed in that model's `images` list in `frontend/js/data/houses.js`, with a short description (alt text) for screen readers and search engines:

```js
images: photos('norway', [
  ['norway.jfif', 'Norway A-frame house with ...'],   // first = main photo
  ['norway1.JPG', 'Norway exterior at dusk ...'],     // the rest = gallery
]),
```

To add a photo, copy it into the model's folder and add one line to the list. To change the order, move the lines.

**File names are case-sensitive on most web servers:** `norway1.JPG` and `norway1.jpg` are different files. Write the name exactly as it appears in the folder.

## Tips

- Landscape photos in 3:2 format fit the cards and the main detail photo without cropping. Gallery tiles are square, so photos are cropped there; clicking a tile shows the full photo.
- Large files slow the site down, especially on phones. Several photos are 2–7 MB (most of `norway/`, `atlantic/atlantic5.png`). Smaller copies (about 1600 px wide, compressed with a tool such as squoosh.app) would load much faster.
