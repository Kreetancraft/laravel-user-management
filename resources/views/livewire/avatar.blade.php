{{-- Self-contained: it hears `media-picked` and saves, so it works on a page
     with no form of its own. --}}
<div>
    <x-user-management::avatar-field
        :items="$items"
        :group="$this->group()"
        :label="__('Avatar')"
    />
</div>
