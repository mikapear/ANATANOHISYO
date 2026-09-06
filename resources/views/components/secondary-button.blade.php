<button {{ $attributes->merge(['type' => 'button', 'class' => 'secondary-action justify-center focus:outline-none focus:ring-2 focus:ring-[#654b7d] focus:ring-offset-2 disabled:opacity-25']) }}>
    {{ $slot }}
</button>

