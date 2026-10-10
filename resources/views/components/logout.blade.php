<form action="{{ route('logout') }}" method="POST">
    @csrf
    <button type="submit" class="button button-secondary w-full"><x-icon name="logout" />Keluar</button>
</form>
