/*
 * Full-screen photo viewer ("lightbox") built on the native <dialog> element.
 *
 * openLightbox(images, startIndex) shows one image at a time, uncropped,
 * with previous/next buttons, arrow-key navigation and a counter.
 * <dialog> already handles Escape to close and keeps keyboard focus inside.
 */

let dialog;
let images = [];
let index = 0;

function createDialog() {
  dialog = document.createElement('dialog');
  dialog.className = 'lightbox';
  dialog.setAttribute('aria-label', 'Photo viewer');
  dialog.innerHTML = `
    <figure class="lightbox__figure">
      <img class="lightbox__image" alt="">
      <figcaption class="lightbox__caption">
        <span data-lightbox-caption></span>
        <span class="lightbox__counter" data-lightbox-counter></span>
      </figcaption>
    </figure>
    <button class="lightbox__btn lightbox__btn--close" type="button" data-lightbox-close aria-label="Close">&times;</button>
    <button class="lightbox__btn lightbox__btn--prev" type="button" data-lightbox-prev aria-label="Previous photo">&lsaquo;</button>
    <button class="lightbox__btn lightbox__btn--next" type="button" data-lightbox-next aria-label="Next photo">&rsaquo;</button>`;

  dialog.querySelector('[data-lightbox-close]').addEventListener('click', () => dialog.close());
  dialog.querySelector('[data-lightbox-prev]').addEventListener('click', () => show(index - 1));
  dialog.querySelector('[data-lightbox-next]').addEventListener('click', () => show(index + 1));
  dialog.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') show(index - 1);
    if (event.key === 'ArrowRight') show(index + 1);
  });
  // Clicking the dark background (outside the photo) closes the viewer.
  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) dialog.close();
  });
  // Close when the user navigates to another page with the browser buttons.
  window.addEventListener('hashchange', () => dialog.open && dialog.close());

  document.body.append(dialog);
}

function show(newIndex) {
  // Wrap around: "next" on the last photo goes back to the first.
  index = (newIndex + images.length) % images.length;
  const { src, alt } = images[index];
  const img = dialog.querySelector('.lightbox__image');
  img.src = src;
  img.alt = alt;
  dialog.querySelector('[data-lightbox-caption]').textContent = alt;
  dialog.querySelector('[data-lightbox-counter]').textContent = `${index + 1} / ${images.length}`;
}

export function openLightbox(imageList, startIndex = 0) {
  if (!dialog) createDialog();
  images = imageList;
  show(startIndex);
  if (!dialog.open) dialog.showModal();
}
