@section('title', $title)
<x-layouts.app-shell :area="\App\Enums\AppArea::Kitchen">
    <div class="view-head">
        <h1>{{ $title }}</h1>
        <p>Redigér opskriften og dens ingredienser.</p>
    </div>

    <form id="form" wire:submit.prevent="save" class="card">
        <div class="form-group">
            <label for="name">Titel</label>
            <input type="text" tabindex="1" wire:model="name" id="name" class="form-control noEnterSubmit" placeholder="Titel">
        </div>

        <div class="form-group">
            <label for="arrtibutevalue">
                Tilføj ingrediens
                <i class="fa fa-info-circle" title="Hjælp" data-toggle="popover" data-placement="top"
                    data-content="Skriv én ingrediens ad gangen med mængde, og tryk på fluebenet.<br>Ingredienserne vises i den rækkefølge de tilføjes.<br><b>Start med # og det bliver en overskrift.</b><br><span style='font-size:0.7rem;'>fx 50 g hakkede nødder</span>"></i>
            </label>
            <div class="attline" style="display: flex; gap: 10px;">
                <input tabindex="2" type="text" wire:model="newIngredient" wire:keydown.enter="addIngredient" id="arrtibutevalue" class="form-control noEnterSubmit" style="flex: 1;">
                <div wire:ignore>
                    <a href="javascript:void(0);" wire:click="addIngredient" id="attlink" class="btn btn-success" style="margin-top: 0;"><i class="fa fa-check"></i></a>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Ingredienser</label>
            <ul class="ingredients" id="ingredients" style="list-style: none; margin: 0; padding: 0;">
                @foreach ($ingredients as $index => $ingredient)
                    <li wire:key="ingredient-{{ $index }}" style="display: flex; align-items: center; gap: 10px; padding: 6px 0; border-bottom: 1px solid var(--line);">
                        <input type="hidden" wire:model="ingredients.{{ $index }}" value="{{ $ingredient }}">
                        <input disabled="disabled" class="attributevalue" value="{{ $ingredient }}" style="flex: 1; background: transparent; border: 0; color: var(--ink);">
                        <a href="#" style="cursor: grab; color: var(--ink-soft);"><i class="fa fa-arrows-v"></i></a>
                        <a href="javascript:void(0);" wire:click="removeIngredient({{ $loop->index }})" style="color: #e0533f;"><i class="fa fa-times"></i></a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="form-group">
            <label for="description">
                Beskrivelse
                <i class="fa fa-info-circle" title="Hjælp" data-toggle="popover" data-placement="top"
                    data-content="Beskriv hvordan maden laves.<br><span style='font-size:0.7rem;'>fx Pisk æggene inden de langsomt tilsættes blandingen.</span>"></i>
            </label>
            <textarea tabindex="3" rows="10" id="description" wire:model="description" class="form-control"></textarea>
        </div>

        <div class="form-group">
            <label for="note">
                Note
                <i class="fa fa-info-circle" title="Hjælp" data-toggle="popover" data-placement="top"
                    data-content="Notér ting du vil huske om opskriften.<br><span style='font-size:0.7rem;'>fx Smager godt med creme fraiche.</span>"></i>
            </label>
            <textarea tabindex="4" rows="10" id="note" wire:model="note" class="form-control"></textarea>
        </div>

        <div class="form-group" wire:ignore>
            <label for="selectCategories">
                Kategorier
                <i class="fa fa-info-circle" title="Hjælp" data-toggle="popover" data-placement="top"
                    data-content="Vælg én eller flere kategorier for opskriften. Det gør det nemmere at finde den igen."></i>
            </label>
            <select tabindex="5" multiple="multiple" id="selectCategories" wire:model="categories" class="form-control" style="height: 200px;" data-usesprite="smallIcons">
                @foreach (\App\Models\Category::with('icon')->forAuthUser()->get() as $index => $category)
                    <option wire:key="category-{{ $index }}" value="{{ $category->id }}" data-icon="{{ $category->icon->class }}"
                        @if ($recipe->categories->contains($category->id)) selected @endif>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group" wire:ignore>
            <label for="tags">
                Tags
                <i class="fa fa-info-circle" title="Hjælp" data-toggle="popover" data-placement="top"
                    data-content="Tags gør det nemmere at finde opskriften igen. Det kan være ingredienser, allergener eller anledningen den serveres til."></i>
            </label>
            <input data-tab="6" type="text" id="tags" wire:model="tags" class="tags-input form-control">
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                <input tabindex="7" name="public" value="1" type="checkbox" wire:model="public"> Skal opskriften være offentlig?
            </label>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 20px;">
            <input tabindex="8" id="submit" type="submit" name="edit" class="btn btn-success noEnterSubmit" value="Gem">
            <a href="{{ route('single', ['id' => $id]) }}" class="btn btn-default" wire:navigate>Annuller</a>
        </div>
    </form>

    @script
    <script>
        $(document).ready(function () {
            $('.noEnterSubmit').keypress(function (e) {
                if (e.which == 13 && !$("#submit").is(":focus"))
                    e.preventDefault();
            });
            $('#selectCategories').multiSelect({
                keepOrder: true,
                selectableHeader: "<div style='text-align:center;font-weight:bold;font-size:12px;'>Ikke valgt</div>",
                selectionHeader: "<div style='text-align:center;font-weight:bold;font-size:12px;'>Valgt</div>",
            });
            setTimeout(() => {
                $('#selectCategories').val(@json($selectedCategories));
                $('#selectCategories').multiSelect('refresh');
            }, 500);

            $('[data-toggle="popover"]').popover({
                html: true
            });
            $('#attlink').hide();

            $('#arrtibutevalue').keydown(function (e) {
                if (e.keyCode == 13) {
                    $('#attlink').click();
                }
            });
            $('#arrtibutevalue').keyup(function (event) {
                if ($('#arrtibutevalue').val().length > 0) {
                    $('#attlink').fadeIn();
                }
                if ($('#arrtibutevalue').val().length === 0) {
                    $('#attlink').fadeOut();
                }
            });

            $('#tags').tagsinput();
            $('#tags').attr('wire:model', 'tags');
            $('#tags').tagsinput('add', @json($tags));
            $('#tags').on('itemAdded', function(event) {
                @this.set('tags', $(this).val());
            });
        });

        $('#selectCategories').on('change', function() {
            @this.set('categories', $(this).val());
        });

        $('#ingredients').sortable(
            {
                update: function(event, ui) {
                    let ingredients = [];
                    $('#ingredients li').each(function() {
                        ingredients.push($(this).find('input').val());
                    });
                    @this.set('ingredients', ingredients);
                }
            }
        );
    </script>
    @endscript
</x-layouts.app-shell>
