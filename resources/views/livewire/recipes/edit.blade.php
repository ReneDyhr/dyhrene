@section('title', $title)
<div>
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content">
            <div class="col-12">
                <div class="new-recipe">
                    <div class="col-6">
                        <form id="form" wire:submit.prevent="save">
                            <h1>
                                <input type="text" tabindex="1" wire:model="name"
                                    class="title noEnterSubmit"
                                    placeholder="Title">
                            </h1>
                            <div class="ingredients-list form-group">
                                <label for="arrtibutevalue">
                                    Add Ingredient
                                    <i class="fa fa-info-circle" title="Help" data-toggle="popover"
                                    data-placement="top"
                                    data-content="Type one ingredient at a time, with the amount and press the tick.<br>The ingredients will be listed from first to last added ingredient.<br>If there's a mistake or an ingredient need a correction, add the corrected ingredient to the list, move it using the arrows, and delete the old one pressing the cross.<br><b>Start with a # and it will be a headline</b><br><span style='font-size:0.7rem;'>eg. 50 g chopped nuts, of choice</span>">
                                    </i>
                                </label>
                                <div class="attline">
                                    <div class="attval">
                                        <input tabindex="2" type="text" wire:model="newIngredient" wire:keydown.enter="addIngredient" id="arrtibutevalue"
                                            class="noEnterSubmit" value="">
                                    </div>
                                    <div class="attvaladdl" wire:ignore>
                                        <a href="javascript:void(0);" wire:click="addIngredient" id="attlink">
                                            <i class="fa fa-check"></i>
                                        </a>
                                    </div>
                                    <div class="clear"></div>
                                </div>
                            </div>

                            <div class="ingredients-list form-group">
                                <label>Ingredients List</label>
                                <ul class="ingredients" id="ingredients">
                                    @foreach ($ingredients as $index => $ingredient)
                                        <li wire:key="ingredient-{{ $index }}">
                                            <input type="hidden" wire:model="ingredients.{{ $index }}" value="{{$ingredient}}">
                                            <input disabled="disabled" class="attributevalue" value="{{$ingredient}}">
                                            <a href="#">
                                                <i class="fa fa-arrows-v"></i>
                                            </a>
                                            <a href="javascript:void(0);" wire:click="removeIngredient({{ $loop->index }})">
                                                <i class="fa fa-times"></i>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="clear"></div>
                            </div>
                            <div class="form-group">
                                <label for="description">
                                    Description
                                    <i class="fa fa-info-circle" title="Help" data-toggle="popover"
                                    data-placement="top"
                                    data-content="Describe how to make the food.<br><span style='font-size:0.7rem;'>eg. Whip the eggs before slowly adding them to the mixture.<br>Baked for 20 mins. at 180 degrees.<br>Make sure the cake has been in the oven for at least 20 mins before opening. It can collapse!</span>">
                                    </i>
                                </label>
                                <textarea
                                    tabindex="3"
                                    rows="10"
                                    id="description"
                                    wire:model="description"
                                    class="text-field form-control">
                                </textarea>
                            </div>
                            <div class="form-group">
                                <label for="note">Note
                                    <i class="fa fa-info-circle" title="Help" data-toggle="popover"
                                    data-placement="top"
                                    data-content="Note things you want to remember with this recipe.<br><span style='font-size:0.7rem;'>eg. Works well, served with sour cream.<br>Martin enjoys this very much.</span>">
                                    </i>
                                </label>
                                <textarea tabindex="4" rows="10" id="note" wire:model="note"
                                        class="text-field form-control"></textarea>
                            </div>
                            <div class="category-list form-group" wire:ignore>
                                <label for="selectCategories">Categories                                <i class="fa fa-info-circle" title="Help" data-toggle="popover"
                                    data-placement="top"
                                    data-content="Choose one or more categories for the recipe. This will help finding the recipe again, even if you forget the title you give it."></i>
                                </label>
                                <select tabindex="5" multiple="multiple" id="selectCategories" wire:model="categories"
                                        class="form-control" style="height:200px;"
                                        data-usesprite="smallIcons">
                                    @foreach (\App\Models\Category::with('icon')->forAuthUser()->get() as $index => $category)
                                        <option wire:key="category-{{ $index }}" value="{{$category->id}}"
                                            data-icon="{{$category->icon->class}}"
                                            @if ($recipe->categories->contains($category->id))
                                                selected
                                            @endif
                                        >{{$category->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="tags form-group" wire:ignore>
                                <label for="tags">Tags
                                    <i class="fa fa-info-circle" title="Help" data-toggle="popover"
                                    data-placement="top"
                                    data-content="Adding tags to a recipe, can make it easier to find it again. It can be ingredients, allergy-prone ingredients or the appropriate celebration, in which it's served.<br><span style='font-size:0.7rem;'>eg. christmas, nuts, gluten, chocolate</span>"></i>
                                </label>
                                <input data-tab="6" type="text" id="tags" wire:model="tags" class="tags-input">
                            </div>
                            <div class="form-check" style="margin-bottom:10px;">
                                <label class="form-check-label">
                                    <input  tabindex="7" name="public"
                                        value="1" type="checkbox" wire:model="public"
                                        class="form-check-input"> Should this be public?</label>
                            </div>
                            <div class="form-group">
                                <input tabindex="8" id="submit" type="submit" name="edit"
                                    class="noEnterSubmit btn btn-success" value="Save Changes">
                            </div>
                            <div class="clear"></div>
                        </form>
                    </div>
                    <div class="clear"></div>
                </div>
            </div>
            @script
            <script>
                let initialized = false;
                $(document).ready(function () {
                    $('.noEnterSubmit').keypress(function (e) {
                        if (e.which == 13 && !$("#submit").is(":focus"))
                            e.preventDefault();
                    });
                    $('#selectCategories').multiSelect({
                        keepOrder: true,
                        selectableHeader: "<div style='text-align:center;font-weight:bold;font-size:12px;'>Not Choosen</div>",
                        selectionHeader: "<div style='text-align:center;font-weight:bold;font-size:12px;'>Choosen</div>",
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
        </div>
            <script>
                $(function () {
                    $('[data-toggle="tooltip"]').tooltip()
                })
            </script>
            <div class="alert alert-info" role="alert"><header>Information</header><main><span class="alert_text"></span></main></div>
        <div class="alert alert-success" role="alert"><header>Success</header><main><span class="alert_text"></span></main></div>
        <div class="alert alert-warning" role="alert"><header>Warning</header><main><span class="alert_text"></span></main></div>
        <div class="alert alert-danger" role="alert"><header>Error</header><main><span class="alert_text"></span></main></div>
    </div>
</div>