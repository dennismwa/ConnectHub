# Story media quality and responsive-fit update

- Story image uploads are now stored in their original JPG, PNG or WebP encoding and dimensions. They are no longer reduced to 1600px or recompressed for the main story asset.
- A separate 420px thumbnail is generated for lightweight previews.
- The full-screen viewer uses `object-fit: contain` for original images and videos, so portrait, square and landscape media remain fully visible without stretching or cropping.
- A softly blurred, darkened image backdrop fills unused screen space for image stories. Video retains its native aspect ratio against a dark canvas.
- The preview and story viewer adapt to available viewport dimensions and safe-area insets.
- No database migration is required; existing story records remain compatible. Existing images already processed by the earlier version cannot regain pixels lost before this update. New uploads receive the preservation behaviour.

## Deployment
1. Deploy the updated project files.
2. Keep existing uploads and story files; do not replace the uploads directory.
3. Ensure the story upload directory remains writable.
4. Test new portrait, square and landscape image uploads, plus portrait and landscape MP4/WebM videos on mobile and desktop.

## Quality note
Original files are preserved as uploaded, while thumbnails are derived separately. This maximises fidelity. As with any original-file preservation workflow, ensure the platform's upload privacy policy and metadata handling are reviewed before public deployment.
