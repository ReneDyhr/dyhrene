<div>
    @section('title', '3D Printing')
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <div class="col-12">
                <div class="storage-list">
                    <div class="recipe">
                        <h1 style="margin: 0 0 30px 0;">3D Printing</h1>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                            <!-- Print Jobs -->
                            <a href="{{ route('print-jobs.index') }}" 
                               style="display: block; padding: 20px; border: 2px solid #ddd; border-radius: 8px; text-decoration: none; color: inherit; transition: all 0.3s; background: #fff;"
                               onmouseover="this.style.borderColor='#007bff'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.1)';"
                               onmouseout="this.style.borderColor='#ddd'; this.style.boxShadow='none';">
                                <div style="font-size: 2em; margin-bottom: 10px; color: #007bff;">
                                    <i class="fa fa-cube"></i>
                                </div>
                                <h3 style="margin: 0 0 10px 0; color: #333;">Print Jobs</h3>
                                <p style="margin: 0; color: #666; font-size: 0.9em;">Manage and track all 3D printing jobs</p>
                            </a>

                            <!-- Print Customers -->
                            <a href="{{ route('print-customers.index') }}" 
                               style="display: block; padding: 20px; border: 2px solid #ddd; border-radius: 8px; text-decoration: none; color: inherit; transition: all 0.3s; background: #fff;"
                               onmouseover="this.style.borderColor='#28a745'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.1)';"
                               onmouseout="this.style.borderColor='#ddd'; this.style.boxShadow='none';">
                                <div style="font-size: 2em; margin-bottom: 10px; color: #28a745;">
                                    <i class="fa fa-users"></i>
                                </div>
                                <h3 style="margin: 0 0 10px 0; color: #333;">Customers</h3>
                                <p style="margin: 0; color: #666; font-size: 0.9em;">Manage customer information</p>
                            </a>

                            <!-- Print Materials -->
                            <a href="{{ route('print-materials.index') }}" 
                               style="display: block; padding: 20px; border: 2px solid #ddd; border-radius: 8px; text-decoration: none; color: inherit; transition: all 0.3s; background: #fff;"
                               onmouseover="this.style.borderColor='#ffc107'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.1)';"
                               onmouseout="this.style.borderColor='#ddd'; this.style.boxShadow='none';">
                                <div style="font-size: 2em; margin-bottom: 10px; color: #ffc107;">
                                    <i class="fa fa-flask"></i>
                                </div>
                                <h3 style="margin: 0 0 10px 0; color: #333;">Materials</h3>
                                <p style="margin: 0; color: #666; font-size: 0.9em;">Manage printing materials and their properties</p>
                            </a>

                            <!-- Print Material Types -->
                            <a href="{{ route('print-material-types.index') }}" 
                               style="display: block; padding: 20px; border: 2px solid #ddd; border-radius: 8px; text-decoration: none; color: inherit; transition: all 0.3s; background: #fff;"
                               onmouseover="this.style.borderColor='#17a2b8'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.1)';"
                               onmouseout="this.style.borderColor='#ddd'; this.style.boxShadow='none';">
                                <div style="font-size: 2em; margin-bottom: 10px; color: #17a2b8;">
                                    <i class="fa fa-tags"></i>
                                </div>
                                <h3 style="margin: 0 0 10px 0; color: #333;">Material Types</h3>
                                <p style="margin: 0; color: #666; font-size: 0.9em;">Manage material type categories</p>
                            </a>

                            <!-- Print Settings -->
                            <a href="{{ route('print-settings.edit') }}" 
                               style="display: block; padding: 20px; border: 2px solid #ddd; border-radius: 8px; text-decoration: none; color: inherit; transition: all 0.3s; background: #fff;"
                               onmouseover="this.style.borderColor='#6c757d'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.1)';"
                               onmouseout="this.style.borderColor='#ddd'; this.style.boxShadow='none';">
                                <div style="font-size: 2em; margin-bottom: 10px; color: #6c757d;">
                                    <i class="fa fa-cog"></i>
                                </div>
                                <h3 style="margin: 0 0 10px 0; color: #333;">Settings</h3>
                                <p style="margin: 0; color: #666; font-size: 0.9em;">Configure printing calculation settings</p>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>

