{{-- Shared fields for Post Job (UC09) and Edit Posted Job (UC11). Pass $job or null. --}}

<div class="form-group">
    <label for="title" class="form-label">Job title</label>
    <input id="title" name="title" type="text" maxlength="120"
           class="form-input @error('title') is-invalid @enderror"
           value="{{ old('title', $job?->title) }}" required>
    @error('title')
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>

<div class="form-group">
    <label for="description" class="form-label">Description</label>
    <textarea id="description" name="description" rows="5" maxlength="5000"
              class="form-textarea @error('description') is-invalid @enderror"
              required>{{ old('description', $job?->description) }}</textarea>
    @error('description')
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>

<div class="form-grid">
    <div class="form-group">
        <label for="category" class="form-label">Category</label>
        <select id="category" name="category" class="form-select @error('category') is-invalid @enderror" required>
            <option value="" disabled @selected(old('category', $job?->category?->value) === null)>Select a category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->value }}" @selected(old('category', $job?->category?->value) === $category->value)>
                    {{ $category->label() }}
                </option>
            @endforeach
        </select>
        @error('category')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="form-group">
        <label for="location_id" class="form-label">Location</label>
        <select id="location_id" name="location_id" class="form-select @error('location_id') is-invalid @enderror" required>
            <option value="" disabled @selected(old('location_id', $job?->location_id) === null)>Select a district</option>
            @foreach ($locations as $state => $districts)
                <optgroup label="{{ $state }}">
                    @foreach ($districts as $location)
                        <option value="{{ $location->id }}" @selected((int) old('location_id', $job?->location_id) === $location->id)>
                            {{ $location->label() }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        @error('location_id')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="form-group">
        <label for="pay_rate" class="form-label">Pay rate ({{ config('gigs.currency') }})</label>
        <input id="pay_rate" name="pay_rate" type="number" step="0.01" min="1" max="999999.99"
               class="form-input @error('pay_rate') is-invalid @enderror"
               value="{{ old('pay_rate', $job?->pay_rate) }}" required>
        @error('pay_rate')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="form-group">
        <label for="pay_type" class="form-label">Pay type</label>
        <select id="pay_type" name="pay_type" class="form-select @error('pay_type') is-invalid @enderror" required>
            @foreach ($payTypes as $payType)
                <option value="{{ $payType->value }}" @selected(old('pay_type', $job?->pay_type?->value) === $payType->value)>
                    {{ $payType->label() }}
                </option>
            @endforeach
        </select>
        @error('pay_type')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="form-group">
        <label for="start_date" class="form-label">Start date</label>
        <input id="start_date" name="start_date" type="date" min="{{ now()->toDateString() }}"
               class="form-input @error('start_date') is-invalid @enderror"
               value="{{ old('start_date', $job?->start_date?->toDateString()) }}" required>
        @error('start_date')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="form-group">
        <label for="end_date" class="form-label">End date</label>
        <input id="end_date" name="end_date" type="date" min="{{ now()->toDateString() }}"
               class="form-input @error('end_date') is-invalid @enderror"
               value="{{ old('end_date', $job?->end_date?->toDateString()) }}" required>
        <p class="form-hint">A job can last at most {{ $maxDays }} days, counting the start and end dates.</p>
        @error('end_date')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </div>
</div>
