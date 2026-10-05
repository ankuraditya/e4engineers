# Video architecture
Videos store provider metadata rather than iframe HTML. YouTube IDs must match the eleven-character provider format and Vimeo IDs must be numeric. Embed URLs are generated on trusted `youtube-nocookie.com` or Vimeo origins. External URLs accept only HTTP/HTTPS. Thumbnails reuse Media and public APIs return published records only.
