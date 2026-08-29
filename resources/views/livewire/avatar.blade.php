{{-- Self-contained: it hears `media-picked` and saves, so it works on a page
     with no form of its own.

     An uploader when one is configured, the library chooser otherwise. On a
     profile page the uploader is the right control: someone setting their own
     picture should not be shown everyone else's files, nor need a permission
     over the media library to do it. --}}
<div>
    @php($uploader = \Kreetancraft\UserManagement\Support\Avatar::uploader())
    @php($subject = $this->subject())

    @if ($uploader && $subject)
        @livewire($uploader, [
            'model' => $subject,
            'collection' => \Kreetancraft\UserManagement\Support\Avatar::collectionName(),
            'group' => $this->group(),
        ], key('avatar-uploader-'.$userId))
    @else
        <x-user-management::avatar-field
            :items="$items"
            :group="$this->group()"
            :label="__('Avatar')"
        />
    @endif
</div>
