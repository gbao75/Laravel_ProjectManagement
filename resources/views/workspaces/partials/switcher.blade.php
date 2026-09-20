<div class="workspace-switcher">
    @if (isset($switcherWorkspaces) && $switcherWorkspaces->isNotEmpty())
        <div class="workspace-switcher__heading">
            <span class="workspace-switcher__icon" aria-hidden="true">
                W
            </span>

            <div class="workspace-switcher__info">
                <span class="workspace-switcher__caption">
                    Đang làm việc tại
                </span>

                <strong title="{{ $currentWorkspace?->name }}">
                    {{ $currentWorkspace?->name }}
                </strong>
            </div>
        </div>

        <form
            method="POST"
            action="{{ route('workspaces.switch') }}"
            class="workspace-switcher__form"
        >
            @csrf

            <label for="workspace-switcher">
                Chọn không gian làm việc
            </label>

            <div class="workspace-switcher__select-wrap">
                <select id="workspace-switcher" name="workspace_id">
                    @foreach ($switcherWorkspaces as $item)
                        <option
                            value="{{ $item->id }}"
                            @selected($currentWorkspace?->id === $item->id)
                        >
                            {{ $item->name }}
                        </option>
                    @endforeach
                </select>

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    aria-hidden="true"
                >
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </div>

            @error('workspace_id')
                <p class="field-error">{{ $message }}</p>
            @enderror

            <button type="submit" class="workspace-switcher__button">
                Chuyển workspace
                <span aria-hidden="true">→</span>
            </button>
        </form>
    @else
        <p class="workspace-switcher__empty">
            Chọn hoặc tạo không gian làm việc để bắt đầu.
        </p>

        <a
            href="{{ route('workspaces.index') }}"
            class="workspace-switcher__link"
        >
            Xem workspace →
        </a>
    @endif
</div>