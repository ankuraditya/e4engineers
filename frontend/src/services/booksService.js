import { apiRequest } from "./apiClient.js";

const query = (params = {}) => {
  const search = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => value !== "" && value !== null && value !== undefined && search.set(key, value));
  return search.size ? `?${search}` : "";
};

export const booksService = {
  getBooks: (params) => apiRequest(`/books${query(params)}`),
  getBook: (slug) => apiRequest(`/books/${encodeURIComponent(slug)}`),
};

export function mapBook(book) {
  const stock = book.inventory?.status === "OUT_OF_STOCK" ? "Out of Stock" : book.inventory?.status === "LOW_STOCK" ? "Low Stock" : "In Stock";
  return {
    ...book,
    discipline: book.disciplines?.length ? book.disciplines.map((item) => item.name).join(', ') : book.discipline?.name || "Engineering",
    category: book.category?.name || "Engineering Book",
    author: book.author_summary || "E4ENGINEERS Author",
    publisher: book.publisher?.name || "E4ENGINEERS Academic Publications",
    price: Number(book.selling_price ?? book.price ?? 0),
    mrp: Number(book.mrp ?? 0),
    image: book.cover?.url || "/books/electrical-power-systems.webp",
    stock,
    edition: book.edition || "Latest Edition",
    format: book.format ? book.format[0].toUpperCase() + book.format.slice(1) : "Paperback",
  };
}
