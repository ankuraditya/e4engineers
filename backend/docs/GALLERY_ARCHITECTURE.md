# Gallery architecture
Albums organize Phase 05 Media references and never own or delete shared media. Covers use explicit `cover_media_id`; clients may fall back to the first ordered image. Gallery image relations store caption, accessible alt text, featured state and order. Reordering is transactional. Public APIs expose published albums only and paginate listing payloads.
