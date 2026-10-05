import { ArrowRight } from "@phosphor-icons/react";
import { useEffect, useState } from "react";
import { useCart } from "../../state/CartContext";
import { booksService, mapBook } from "../../services/booksService";

const currency = new Intl.NumberFormat("en-IN", { style: "currency", currency: "INR", maximumFractionDigits: 0 });

function BookCard({ book, onAdd }) {
  const savings = book.originalPrice - book.price;
  const unavailable = book.stock === "Out of Stock";
  return (
    <article className="book-card">
      <img className="book-card__cover" src={book.image} alt={`${book.title} book cover`} />
      <div className="book-card__content">
        <div>
          <h3>{book.title}</h3>
          <p className="book-card__author">{book.author}</p>
        </div>
        <div className="book-card__price" aria-label={`Price ${currency.format(book.price)}, original price ${currency.format(book.originalPrice)}, save ${currency.format(savings)}`}>
          <strong>{currency.format(book.price)}</strong>
          <del>{currency.format(book.originalPrice)}</del>
          <span>Save {currency.format(savings)}</span>
        </div>
        <div className="book-card__actions">
          <button type="button" disabled={unavailable} aria-label={`Add ${book.title} to cart`} onClick={()=>onAdd(book)}>{unavailable ? "Out of stock" : "Add to cart"}</button>
          <a href={`/books/${book.slug}`}>View book</a>
        </div>
      </div>
    </article>
  );
}

export function EngineeringBookCatalogue() {
  const { addItemAndOpenCart } = useCart();
  const [books, setBooks] = useState([]);
  const [error, setError] = useState("");
  useEffect(() => { let active = true; booksService.getBooks({ featured: 1, sort: "featured", per_page: 4 }).then((result) => { if (active) setBooks((result.data || []).map((book) => { const mapped = mapBook(book); return { ...mapped, originalPrice: mapped.mrp }; })); }).catch(() => { if (active) setError("Featured books are temporarily unavailable."); }); return () => { active = false; }; }, []);
  if (!books.length) return null;
  return (
    <section className="engineering-books" aria-labelledby="engineering-books-heading">
      <div className="container engineering-books__inner">
        <div className="section-heading">
          <div>
            <h2 id="engineering-books-heading">Engineering Book Catalogue</h2>
            <p>Explore selected engineering books for academic and professional learning.</p>
          </div>
          <a className="section-heading__link" href="/books">View all books <ArrowRight aria-hidden="true" weight="bold" /></a>
        </div>
        <div className="book-grid">
          {books.map((book) => <BookCard book={book} onAdd={async selected => {
            try { await addItemAndOpenCart(selected); }
            catch (reason) { setError(reason.message || "Unable to add this book to your cart."); }
          }} key={book.id} />)}
        </div>
        {error && <p role="status">{error}</p>}
      </div>
    </section>
  );
}
