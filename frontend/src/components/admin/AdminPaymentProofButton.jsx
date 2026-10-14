import { useEffect, useState } from "react";
import { AccessibleModal } from "../shared/AccessibleModal.jsx";
import { loadPaymentProof } from "../../services/adminPaymentService.js";

export function AdminPaymentProofButton({ attemptId, label = "View screenshot", className = "" }) {
  const [imageUrl, setImageUrl] = useState("");
  const [extension, setExtension] = useState("png");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => () => { if (imageUrl) URL.revokeObjectURL(imageUrl); }, [imageUrl]);

  async function showProof() {
    setError("");
    setLoading(true);
    try {
      const blob = await loadPaymentProof(attemptId);
      setExtension(blob.type === "image/jpeg" ? "jpg" : blob.type === "image/webp" ? "webp" : "png");
      setImageUrl(URL.createObjectURL(blob));
    } catch (requestError) {
      setError(requestError.message);
    } finally {
      setLoading(false);
    }
  }

  return <>
    <button type="button" className={className} disabled={loading} onClick={showProof}>{loading ? "Loading screenshot…" : label}</button>
    {error && <span className="admin-proof-error" role="alert">{error}</span>}
    {imageUrl && <AccessibleModal title="Payment screenshot" onClose={() => setImageUrl("")} className="admin-proof-modal">
      <img className="admin-proof-image" src={imageUrl} alt="Customer-uploaded payment screenshot" />
      <a className="button button--secondary" href={imageUrl} download={`payment-proof-${attemptId}.${extension}`}>Download screenshot</a>
    </AccessibleModal>}
  </>;
}
