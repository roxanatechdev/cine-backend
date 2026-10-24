@if ($currentSort !== $campo)
<span class="material-symbols-outlined opacity-25 fs-6">unfold_more</span>
@elseif ($currentDirection === 'asc')
<span class="material-symbols-outlined text-danger fs-6">arrow_upward</span>
@else
<span class="material-symbols-outlined text-danger fs-6">arrow_downward</span>
@endif