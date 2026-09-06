<button {{ $attributes->merge(['type' => 'submit', 'class' => 'primary-action border border-transparent focus:outline-none focus:ring-2 focus:ring-[#654b7d] focus:ring-offset-2']) }}>
    {{ $slot }}
</button>

