{{-- Stands in for a host application's layout, so the resolver can be tested
     against one that actually exists. --}}
<!DOCTYPE html><html><body>{{ $slot ?? '' }}</body></html>
