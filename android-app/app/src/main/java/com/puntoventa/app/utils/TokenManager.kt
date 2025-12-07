package com.puntoventa.app.utils

import android.content.Context
import android.content.SharedPreferences

class TokenManager(context: Context) {
    private val prefs: SharedPreferences = 
        context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
    
    companion object {
        private const val PREFS_NAME = "app_prefs"
        private const val KEY_TOKEN = "auth_token"
        private const val KEY_USER_ID = "user_id"
        private const val KEY_USER_NAME = "user_name"
        private const val KEY_USER_EMAIL = "user_email"
        private const val KEY_USER_ROLE = "user_role"
    }
    
    fun saveToken(token: String) {
        prefs.edit().putString(KEY_TOKEN, token).apply()
    }
    
    fun getToken(): String? {
        return prefs.getString(KEY_TOKEN, null)
    }
    
    fun saveUser(userId: String, name: String, email: String, role: String) {
        prefs.edit().apply {
            putString(KEY_USER_ID, userId)
            putString(KEY_USER_NAME, name)
            putString(KEY_USER_EMAIL, email)
            putString(KEY_USER_ROLE, role)
            apply()
        }
    }
    
    fun getUserId(): String? = prefs.getString(KEY_USER_ID, null)
    fun getUserName(): String? = prefs.getString(KEY_USER_NAME, null)
    fun getUserEmail(): String? = prefs.getString(KEY_USER_EMAIL, null)
    fun getUserRole(): String? = prefs.getString(KEY_USER_ROLE, null)
    
    fun clear() {
        prefs.edit().clear().apply()
    }
    
    fun isLoggedIn(): Boolean {
        return getToken() != null
    }
}

