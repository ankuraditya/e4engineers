<?php

namespace App\Services;

use App\Enums\BookStatus;
use App\Enums\CouponDiscountType;
use App\Enums\CouponScope;
use App\Exceptions\CouponValidationException;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\User;

final class CouponService
{
    public function resolve(string $code): Coupon
    {
        $coupon = Coupon::query()->whereRaw('UPPER(code) = ?', [strtoupper(trim($code))])->first();
        if (! $coupon) {
            throw new CouponValidationException('COUPON_NOT_FOUND', 'Invalid coupon code.');
        }

        return $coupon;
    }

    public function apply(Cart $cart, string $code, ?User $user): array
    {
        $coupon = $this->resolve($code);
        $result = $this->calculate($coupon, $cart, $user);
        if (! $result['valid']) {
            throw new CouponValidationException($result['reason_code'], $result['message']);
        } $cart->update(['coupon_id' => $coupon->id, 'coupon_applied_at' => now(), 'last_activity_at' => now()]);

        return $result;
    }

    public function remove(Cart $cart): void
    {
        $cart->update(['coupon_id' => null, 'coupon_applied_at' => null, 'last_activity_at' => now()]);
    }

    public function revalidate(Cart $cart, ?User $user): array
    {
        if (! $cart->coupon_id) {
            return $this->none();
        }$coupon = $cart->coupon;
        if (! $coupon) {
            $this->remove($cart);

            return $this->removed('COUPON_DELETED', 'The applied coupon is no longer available.');
        }$result = $this->calculate($coupon, $cart, $user);
        if (! $result['valid']) {
            $this->remove($cart);

            return $this->removed($result['reason_code'], $result['message']);
        }

        return $result;
    }

    public function calculate(Coupon $coupon, Cart $cart, ?User $user): array
    {
        if ($coupon->trashed()) {
            return $this->invalid('COUPON_DELETED', 'This coupon is no longer available.');
        } if (! $coupon->is_active) {
            return $this->invalid('COUPON_INACTIVE', 'This coupon is inactive.');
        } if ($coupon->starts_at?->isFuture()) {
            return $this->invalid('COUPON_NOT_STARTED', 'This coupon is not active yet.');
        } if ($coupon->expires_at?->isPast()) {
            return $this->invalid('COUPON_EXPIRED', 'This coupon has expired.');
        }
        if ($coupon->usage_limit !== null && $coupon->usages()->count() >= $coupon->usage_limit) {
            return $this->invalid('USAGE_LIMIT_REACHED', 'This coupon has reached its usage limit.');
        } if ($coupon->per_customer_limit !== null && $user && $coupon->usages()->where('user_id', $user->id)->count() >= $coupon->per_customer_limit) {
            return $this->invalid('CUSTOMER_LIMIT_REACHED', 'You have reached the usage limit for this coupon.');
        }
        $cart->loadMissing('items.book');
        $coupon->loadMissing(['books:id', 'categories:id', 'disciplines:id']);
        $eligible = 0;
        foreach ($cart->items as $item) {
            $book = $item->book;
            if (! $book || $book->trashed() || $book->status !== BookStatus::Published || ! $this->eligible($coupon, $book)) {
                continue;
            }$eligible += $this->cents((string) $book->selling_price) * $item->quantity;
        }
        if ($eligible === 0) {
            return $this->invalid('NO_ELIGIBLE_ITEMS', 'This coupon is not applicable to the books in your cart.');
        }$minimum = $coupon->minimum_subtotal === null ? 0 : $this->cents((string) $coupon->minimum_subtotal);
        if ($eligible < $minimum) {
            return $this->invalid('MINIMUM_SUBTOTAL_NOT_MET', 'The eligible cart subtotal does not meet this coupon minimum.');
        }
        $discount = $coupon->discount_type === CouponDiscountType::Percentage ? intdiv($eligible * (int) round((float) $coupon->discount_value * 100) + 5000, 10000) : $this->cents((string) $coupon->discount_value);
        if ($coupon->maximum_discount !== null) {
            $discount = min($discount, $this->cents((string) $coupon->maximum_discount));
        }$discount = min($discount, $eligible);

        return ['valid' => true, 'reason_code' => null, 'message' => 'Coupon applied.', 'coupon' => ['code' => $coupon->code, 'name' => $coupon->name, 'description' => $coupon->description, 'discount_type' => $coupon->discount_type->value, 'discount_value' => $coupon->discount_value], 'eligible_subtotal' => $this->money($eligible), 'discount' => $this->money($discount), 'removed' => false];
    }

    private function eligible(Coupon $coupon, $book): bool
    {
        return match ($coupon->applies_to) {
            CouponScope::AllBooks => true,CouponScope::SpecificBooks => $coupon->books->contains($book->id),CouponScope::BookCategories => $book->category_id !== null && $coupon->categories->contains($book->category_id),CouponScope::EngineeringDisciplines => $coupon->disciplines->contains($book->engineering_discipline_id) || $book->disciplines()->whereIn('engineering_disciplines.id', $coupon->disciplines->pluck('id'))->exists()
        };
    }

    private function none(): array
    {
        return ['valid' => true, 'coupon' => null, 'eligible_subtotal' => '0.00', 'discount' => '0.00', 'removed' => false, 'reason_code' => null, 'message' => null];
    }

    private function invalid(string $code, string $message): array
    {
        return ['valid' => false, 'coupon' => null, 'eligible_subtotal' => '0.00', 'discount' => '0.00', 'removed' => false, 'reason_code' => $code, 'message' => $message];
    }

    private function removed(string $code, string $message): array
    {
        return ['valid' => true, 'coupon' => null, 'eligible_subtotal' => '0.00', 'discount' => '0.00', 'removed' => true, 'reason_code' => 'COUPON_REMOVED_'.$code, 'message' => $message];
    }

    private function cents(string $value): int
    {
        [$whole,$fraction] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function money(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
