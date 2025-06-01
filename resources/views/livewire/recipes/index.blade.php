@section('title', $title)
<div>
@include('components.layouts.sidenav')
<div id="main">
    @include('components.layouts.header')
    <div class="content homepage">
        <div class="col-12 recipe-list">
            <div class="list">
                @foreach ($recipes as $recipe)
                    <div class="recipe">
                        <h1>
                            <a href="/recipe/{{$recipe->id}}">{{$recipe->name}}</a>
                        </h1>
                        <div class="recipe-list">
                            <ul>
                                @foreach ($recipe->ingredients as $ingredient)
                                    @if (str_starts_with($ingredient->name, '#'))
                                        <li><b>{{substr($ingredient->name, 1)}}</b></li>
                                    @else
                                        <li>{{$ingredient->name}}</li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                        @if ($recipe->tags->count() > 0)
                            <div class="tags">
                                <label>Tags:</label>
                                <span>
                                    @foreach($recipe->tags as $index => $tag)
                                        <a href="/tag/{{$tag->name}}">{{$tag->name}}</a>@if($index < count($recipe->tags) - 1), @endif
                                    @endforeach
                                </span>
                                <div class="clear"></div>
                            </div>
                        @endif
                        @if ($recipe->categories->count() > 0)
                            <div class="tags">
                                <label>Categories:</label>
                                <span>
                                    @foreach ($recipe->categories as $index => $category)
                                        <a href="/category/{{$category->slug}}">{{$category->name}}</a>@if($index < count($recipe->categories) - 1), @endif
                                    @endforeach
                                </span>
                                <div class="clear"></div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="clear"></div>
        </div>
    </div>
</div>