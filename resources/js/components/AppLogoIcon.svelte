<script lang="ts">
    let {
        class: className = '',
        ...rest
    }: {
        class?: string;
        [key: string]: unknown;
    } = $props();
</script>

<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 512 512"
    class={className}
    aria-hidden="true"
    {...rest}
>
    <rect width="512" height="512" rx="120" fill="#0D9488" />
    <path
        d="M136 264 216 344 376 168"
        fill="none"
        stroke="#FFFFFF"
        stroke-width="64"
        stroke-linecap="round"
        stroke-linejoin="round"
    />
</svg>
