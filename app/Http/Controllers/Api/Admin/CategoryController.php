<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Exception;
use Illuminate\Http\Request;

class CategoryController extends Controller
{

    public function getCategories()
    {
        try {
            $categories = Category::latest()->get();
            return $this->sendResponse($categories, 'Get categories.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function addCategory(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name',
            ]);
            $category = Category::create($validated);
            return $this->sendResponse($category, 'Category added successfully.', true, 201);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function viewCategory($id)
    {
        try {
            $category = Category::find($id);
            if (!$category) {
                return $this->sendError('Category not found!.', []);
            }
            return $this->sendResponse($category, 'View category.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function editCategory(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name,' . $id,
            ]);
            $category = Category::find($id);
            if (!$category) {
                return $this->sendError('Category not found!.', []);
            }
            $category->update($validated);
            return $this->sendResponse($category, 'Category updated successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function deleteCategory($id)
    {
        try {
            $category = Category::find($id);
            if (!$category) {
                return $this->sendError('Category not found!.', []);
            }
            $category->delete();
            return $this->sendResponse($category, 'Category deleted successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
}
